<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Facture;
use App\Models\FactureDetail;
use App\Models\MouvementStock;
use App\Models\Produit;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// Ce controleur gere les produits.
class ProductController extends Controller
{
    public function __construct(private readonly ActivityService $activityService)
    {
    }

    public function index(Request $request): View
    {
        $products = Produit::with('category')
            // L employe consulte seulement le stock disponible des produits actifs.
            ->when(! $request->user()->isAdministrator(), fn ($query) => $query->where('est_actif', true))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . trim((string) $request->string('search')) . '%';

                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('nom', 'like', $search)
                        ->orWhere('reference', 'like', $search)
                        ->orWhere('description', 'like', $search)
                        ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('nom', 'like', $search));
                });
            })
            ->when($request->filled('letter'), function ($query) use ($request) {
                $letter = trim((string) $request->string('letter'));

                $query->where('nom', 'like', $letter . '%');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('nom')->get();

        return view('products.form', ['product' => new Produit(), 'categories' => $categories]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('produits', 'nom')],
            'reference' => ['required', 'string', 'max:255', 'unique:produits,reference'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            // Cette description est obligatoire pour garder une fiche produit exploitable.
            'description' => ['required', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('products', 'public');
        }

        $product = Produit::create($data + ['is_active' => $request->boolean('is_active', true)]);
        $this->activityService->log('creation_produit', $product);

        return redirect()->route('products.index')->with('success', 'Produit cree.');
    }

    public function edit(Produit $product): View
    {
        $categories = Category::orderBy('nom')->get();

        return view('products.form', compact('product', 'categories'));
    }

    public function update(Request $request, Produit $product): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('produits', 'nom')->ignore($product->id)],
            'reference' => ['required', 'string', 'max:255', 'unique:produits,reference,' . $product->id],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            // Cette description reste obligatoire aussi pendant la modification.
            'description' => ['required', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('products', 'public');
        }

        $product->update($data + ['is_active' => $request->boolean('is_active', true)]);
        $this->activityService->log('modification_produit', $product);

        return redirect()->route('products.index')->with('success', 'Produit mis a jour.');
    }

    public function destroy(Produit $product): RedirectResponse
    {
        $product->load(['factureDetails', 'mouvementsStock']);

        $deletedInvoiceCount = 0;
        $updatedInvoiceCount = 0;
        $deletedDetailCount = 0;
        $deletedMovementCount = 0;

        DB::transaction(function () use (
            $product,
            &$deletedInvoiceCount,
            &$updatedInvoiceCount,
            &$deletedDetailCount,
            &$deletedMovementCount
        ): void {
            $affectedInvoiceIds = $product->factureDetails
                ->pluck('facture_id')
                ->filter()
                ->unique()
                ->values();

            $deletedDetailCount = FactureDetail::where('produit_id', $product->id)->count();
            $deletedMovementCount = MouvementStock::where('produit_id', $product->id)->count();

            FactureDetail::where('produit_id', $product->id)->delete();
            MouvementStock::where('produit_id', $product->id)->delete();

            foreach ($affectedInvoiceIds as $invoiceId) {
                $invoice = Facture::find($invoiceId);

                if (! $invoice) {
                    continue;
                }

                $remainingTotalHt = (float) $invoice->details()->sum('total_ligne_ht');

                if ($remainingTotalHt <= 0) {
                    $invoice->delete();
                    $deletedInvoiceCount++;
                    continue;
                }

                $taxRate = (float) $invoice->tax_rate;
                $taxAmount = round($remainingTotalHt * $taxRate / 100, 2);

                $invoice->update([
                    'total_ht' => $remainingTotalHt,
                    'tax_amount' => $taxAmount,
                    'total_ttc' => $remainingTotalHt + $taxAmount,
                ]);

                $updatedInvoiceCount++;
            }

            $product->delete();
        });

        $this->activityService->log('suppression_produit', $product, [
            'deleted_invoice_count' => $deletedInvoiceCount,
            'updated_invoice_count' => $updatedInvoiceCount,
            'deleted_detail_count' => $deletedDetailCount,
            'deleted_movement_count' => $deletedMovementCount,
        ]);

        return redirect()->route('products.index')->with(
            'success',
            'Produit supprime avec nettoyage des factures et mouvements lies.'
        );
    }
}
