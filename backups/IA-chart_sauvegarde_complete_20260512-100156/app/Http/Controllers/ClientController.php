<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// Ce contrôleur gère les clients.
class ClientController extends Controller
{
    public function __construct(private readonly ActivityService $activityService)
    {
    }

    public function index(Request $request): View
    {
        $clients = Client::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . trim((string) $request->string('search')) . '%';

                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('nom', 'like', $search)
                        ->orWhere('postnom', 'like', $search)
                        ->orWhere('adresse_email', 'like', $search)
                        ->orWhere('telephone', 'like', $search)
                        ->orWhere('entreprise', 'like', $search);
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('clients.form', ['client' => new Client()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $client = Client::create($request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('clients', 'nom')->where(fn ($query) => $query->where('postnom', $request->input('post_name'))),
            ],
            'post_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:clients,email'],
            'phone' => ['nullable', 'string', 'max:255', 'unique:clients,telephone'],
            'address' => ['nullable', 'string'],
            'company' => ['nullable', 'string', 'max:255'],
        ]));

        $this->activityService->log('creation_client', $client);
        return redirect()->route('clients.index')->with('success', 'Client créé.');
    }

    public function edit(Client $client): View
    {
        return view('clients.form', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $client->update($request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('clients', 'nom')
                    ->where(fn ($query) => $query->where('postnom', $request->input('post_name')))
                    ->ignore($client->id),
            ],
            'post_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:clients,email,' . $client->id],
            'phone' => ['nullable', 'string', 'max:255', 'unique:clients,telephone,' . $client->id],
            'address' => ['nullable', 'string'],
            'company' => ['nullable', 'string', 'max:255'],
        ]));

        $this->activityService->log('modification_client', $client);
        return redirect()->route('clients.index')->with('success', 'Client mis à jour.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();
        $this->activityService->log('suppression_client', $client);
        return redirect()->route('clients.index')->with('success', 'Client supprimé.');
    }
}
