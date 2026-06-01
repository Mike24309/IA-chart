<?php

namespace App\Http\Controllers;

use App\Models\MouvementStock;
use App\Models\Produit;
use App\Services\ActivityService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Ce controleur gere les mouvements de stock.
class StockMovementController extends Controller
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly ActivityService $activityService
    ) {
    }

    public function index(Request $request): View
    {
        $movements = MouvementStock::with(['produit', 'user'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . trim((string) $request->string('search')) . '%';

                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('type_mouvement', 'like', $search)
                        ->orWhereHas('produit', function ($productQuery) use ($search) {
                            $productQuery->where('nom', 'like', $search)
                                ->orWhere('reference', 'like', $search);
                        })
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('nom', 'like', $search)
                                ->orWhere('code_connexion', 'like', $search);
                        });
                });
            })
            ->when($request->filled('letter'), function ($query) use ($request) {
                $letter = trim((string) $request->string('letter'));

                $query->whereHas('produit', fn ($productQuery) => $productQuery->where('nom', 'like', $letter . '%'));
            })
            ->when($request->filled('date'), function ($query) use ($request) {
                $query->whereDate('date_mouvement', $request->input('date'));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('stock-movements.index', compact('movements'));
    }

    public function create(): View
    {
        $products = Produit::orderBy('nom')->get();

        return view('stock-movements.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'produit_id' => ['required', 'exists:produits,id'],
            'movement_type' => ['required', 'in:entree,sortie,ajustement'],
            'quantity' => ['required', 'integer'],
            'new_stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $product = Produit::findOrFail($data['produit_id']);
        $reason = match ($data['movement_type']) {
            'entree' => 'Entree manuelle de stock',
            'sortie' => 'Sortie manuelle de stock',
            default => 'Ajustement manuel de stock',
        };
        $movement = $this->stockService->move(
            $product,
            $request->user(),
            $data['movement_type'],
            (int) $data['quantity'],
            $reason,
            isset($data['new_stock']) ? (int) $data['new_stock'] : null
        );

        $this->activityService->log('mouvement_stock', $movement, $data);

        return redirect()->route('stock-movements.index')->with('success', 'Mouvement de stock enregistre.');
    }
}
