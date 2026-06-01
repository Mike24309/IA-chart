<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Ce contrôleur gère les catégories produit.
class CategoryController extends Controller
{
    public function __construct(private readonly ActivityService $activityService)
    {
    }

    public function index(Request $request): View
    {
        $categories = Category::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . trim((string) $request->string('search')) . '%';

                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('nom', 'like', $search)
                        ->orWhere('description', 'like', $search);
                });
            })
            ->when($request->filled('letter'), function ($query) use ($request) {
                $letter = trim((string) $request->string('letter'));

                $query->where('nom', 'like', $letter . '%');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.form', ['category' => new Category()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = Category::create($request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,nom'],
            'description' => ['nullable', 'string'],
        ]) + ['is_active' => $request->boolean('is_active', true)]);

        $this->activityService->log('creation_categorie', $category);
        return redirect()->route('categories.index')->with('success', 'Catégorie créée.');
    }

    public function edit(Category $category): View
    {
        return view('categories.form', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,nom,' . $category->id],
            'description' => ['nullable', 'string'],
        ]) + ['is_active' => $request->boolean('is_active', true)]);

        $this->activityService->log('modification_categorie', $category);
        return redirect()->route('categories.index')->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();
        $this->activityService->log('suppression_categorie', $category);
        return redirect()->route('categories.index')->with('success', 'Catégorie supprimée.');
    }
}
