<?php

namespace App\Http\Controllers;

use App\Jobs\SendInvoiceEmailJob;
use App\Mail\InvoiceMail;
use App\Models\Client;
use App\Models\Facture;
use App\Models\MouvementStock;
use App\Models\Produit;
use App\Services\ActivityService;
use App\Services\InvoiceService;
use App\Services\ParameterService;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;
use Throwable;

// Ce controleur gere les factures, le PDF et l envoi email.
class InvoiceController extends Controller
{
    // Le constructeur injecte les services metier utilises pour les factures, le stock et le parametrage.
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly ParameterService $parameterService,
        private readonly StockService $stockService,
        private readonly ActivityService $activityService
    ) {
    }

    // Cette methode liste les factures avec des filtres et limite la vue de l employe a ses propres factures.
    public function index(Request $request): View
    {
        // L'administrateur voit toutes les factures, l'employe voit uniquement celles qu'il a creees.
        $invoices = Facture::with(['client', 'user'])
            ->when(! auth()->user()->isAdministrator(), fn ($query) => $query->where('utilisateur_id', auth()->id()))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . trim((string) $request->string('search')) . '%';

                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('numero_facture', 'like', $search)
                        ->orWhere('statut', 'like', $search)
                        ->orWhereHas('client', function ($clientQuery) use ($search) {
                            $clientQuery->where('nom', 'like', $search)
                                ->orWhere('postnom', 'like', $search)
                                ->orWhere('entreprise', 'like', $search)
                                ->orWhere('adresse_email', 'like', $search);
                        })
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('nom', 'like', $search)
                                ->orWhere('adresse_email', 'like', $search)
                                ->orWhere('code_connexion', 'like', $search);
                        });
                });
            })
            ->when($request->filled('letter'), function ($query) use ($request) {
                $letter = trim((string) $request->string('letter'));

                $query->where('numero_facture', 'like', $letter . '%');
            })
            ->when($request->filled('date'), function ($query) use ($request) {
                $query->whereDate('date_facture', $request->input('date'));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('invoices.index', compact('invoices'));
    }

    // Cette methode charge les donnees necessaires au formulaire de creation et a l apercu de facture.
    public function create(): View
    {
        $clients = Client::orderBy('nom')->get();
        $products = Produit::where('est_actif', true)->orderBy('nom')->get();
        $settings = $this->parameterService->current();

        return view('invoices.create', compact('clients', 'products', 'settings'));
    }

    // Cette methode valide la saisie, cree le client si besoin, puis delegue la logique metier au service de facturation.
    public function store(Request $request): RedirectResponse|Response
    {
        $validated = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'client_name' => ['required_without:client_id', 'nullable', 'string', 'max:255'],
            'client_post_name' => ['required_without:client_id', 'nullable', 'string', 'max:255'],
            'client_email' => ['nullable', 'email'],
            'client_phone' => ['nullable', 'string', 'max:255'],
            'client_address' => ['nullable', 'string'],
            'client_company' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'currency_code' => ['required', 'in:USD,FC'],
            'invoice_action' => ['nullable', 'in:create,create_send,create_download'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.produit_id' => ['required', 'exists:produits,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price_ht' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $action = $validated['invoice_action'] ?? 'create';

        if (empty($validated['client_id'])) {
            $client = Client::create([
                'name' => $validated['client_name'],
                'post_name' => $validated['client_post_name'],
                'email' => $validated['client_email'] ?? null,
                'phone' => $validated['client_phone'] ?? null,
                'address' => $validated['client_address'] ?? null,
                'company' => $validated['client_company'] ?? null,
            ]);
            $validated['client_id'] = $client->id;
        }

        try {
            $invoice = $this->invoiceService->create($validated, $request->user());

            if ($action === 'create_send') {
                return $this->queueInvoiceEmail($invoice)
                    ? redirect()->route('invoices.show', $invoice)->with('success', 'Facture creee. L envoi email a ete lance en arriere-plan.')
                    : redirect()->route('invoices.show', $invoice)->withErrors(['email' => 'Facture creee, mais l envoi email n a pas pu etre lance. Verifiez l email du client.']);
            }

            if ($action === 'create_download') {
                return $this->buildPdfResponse($invoice);
            }

            return redirect()->route('invoices.show', $invoice)->with('success', 'Facture creee avec succes.');
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['stock' => $exception->getMessage()]);
        }
    }

    // Cette methode affiche la facture en version ecran avec un rendu proche du PDF final.
    public function show(Facture $invoice): View
    {
        $this->ensureInvoiceAccess($invoice);
        $invoice->load(['client', 'user', 'details.produit']);
        $settings = $this->parameterService->current();

        return view('invoices.show', compact('invoice', 'settings'));
    }

    // Cette methode genere le document PDF telechargeable a partir de la vue Blade dediee.
    public function pdf(Facture $invoice)
    {
        $this->ensureInvoiceAccess($invoice);

        try {
            return $this->buildPdfResponse($invoice);
        } catch (Throwable $exception) {
            $message = 'Impossible de generer le PDF de cette facture. Verifiez le modele et les images parametrees.';

            if (config('app.debug')) {
                $message .= ' Detail : ' . $exception->getMessage();
            }

            return back()->withErrors(['pdf' => $message]);
        }
    }

    // Cette methode envoie la facture en piece jointe PDF au client si une adresse email existe.
    public function email(Facture $invoice): RedirectResponse
    {
        $this->ensureInvoiceAccess($invoice);

        if (! $invoice->client?->email) {
            return back()->withErrors(['email' => 'Ce client n a pas d adresse email.']);
        }

        try {
            if (! $this->queueInvoiceEmail($invoice)) {
                return back()->withErrors(['email' => 'Impossible d envoyer la facture par email. Verifiez la configuration SMTP et l adresse du client.']);
            }

            return back()->with('success', 'Envoi de la facture lance en arriere-plan.');
        } catch (Throwable $exception) {
            return back()->withErrors(['email' => 'Impossible d envoyer la facture par email. Verifiez la configuration SMTP et l adresse du client.']);
        }
    }

    // Cette methode supprime une facture cote administration et remet le stock dans l etat logique precedent.
    public function destroy(Facture $invoice): RedirectResponse
    {
        $invoice->load(['details.produit', 'client', 'user']);

        DB::transaction(function () use ($invoice): void {
            $invoiceNumber = $invoice->invoice_number;

            foreach ($invoice->details as $detail) {
                if ($detail->produit) {
                    // Ce verrou remet le stock au bon niveau meme si d autres mouvements partent en parallele.
                    $produit = Produit::query()->lockForUpdate()->find($detail->produit->id);

                    if ($produit) {
                        $produit->update([
                            'stock' => (int) $produit->stock + (int) $detail->quantity,
                        ]);
                    }
                }
            }

            // Cette suppression retire aussi les anciens mouvements lies a la facture effacee.
            MouvementStock::where('motif', 'like', '%' . $invoiceNumber . '%')->delete();
            $invoice->details()->delete();
            $invoice->delete();
            $this->activityService->log('suppression_facture', null, ['invoice_number' => $invoiceNumber]);
        });

        return redirect()->route('invoices.index')->with('success', 'Facture supprimee et stock remis a jour.');
    }

    // Cette methode empeche un employe de consulter ou telecharger une facture qui ne lui appartient pas.
    private function ensureInvoiceAccess(Facture $invoice): void
    {
        // Cette verification empeche un employe d'ouvrir une facture creee par un autre compte.
        if (! auth()->user()->isAdministrator()) {
            abort_unless((int) $invoice->utilisateur_id === (int) auth()->id(), 403);
        }
    }

    // Cette methode centralise la generation du PDF pour garder le meme rendu partout.
    private function buildPdfResponse(Facture $invoice): Response
    {
        $invoice->load(['client', 'user', 'details.produit']);
        $settings = $this->parameterService->current();

        return Pdf::loadView('invoices.pdf', compact('invoice', 'settings'))
            ->setPaper('a4')
            ->download($invoice->invoice_number . '.pdf');
    }

    // Cette methode planifie l envoi de la facture apres la reponse pour accelerer l usage.
    private function queueInvoiceEmail(Facture $invoice): bool
    {
        if (! $invoice->client?->email) {
            return false;
        }

        dispatch(new SendInvoiceEmailJob($invoice->id))->afterResponse();

        return true;
    }
}
