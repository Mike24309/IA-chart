<?php

namespace App\Http\Controllers;

use App\Services\ActivityService;
use App\Services\ParameterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Ce contrôleur permet à l'administrateur de modifier les paramètres.
class SettingsController extends Controller
{
    public function __construct(
        private readonly ParameterService $parameterService,
        private readonly ActivityService $activityService
    ) {
    }

    public function edit(): View
    {
        $settings = $this->parameterService->current();
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = $this->parameterService->current();

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['nullable', 'email'],
            'company_phone' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string'],
            'currency' => ['required', 'string', 'max:10'],
            'usd_to_fc_rate' => ['required', 'numeric', 'min:0.01'],
            'default_tax_rate' => ['required', 'numeric', 'min:0'],
            'invoice_number_format' => ['required', 'string', 'max:255'],
            'invoice_due_days' => ['required', 'integer', 'min:0'],
            'global_minimum_stock' => ['required', 'integer', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'bank_swift' => ['nullable', 'string', 'max:255'],
            'invoice_terms' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'invoice_background' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'invoice_primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'invoice_secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'invoice_title' => ['required', 'string', 'max:100'],
            'invoice_bill_from_label' => ['required', 'string', 'max:100'],
            'invoice_bill_to_label' => ['required', 'string', 'max:100'],
            'invoice_payment_label' => ['required', 'string', 'max:150'],
            'invoice_signature_label' => ['required', 'string', 'max:100'],
            'invoice_footer_bank_label' => ['required', 'string', 'max:100'],
            'invoice_footer_account_label' => ['required', 'string', 'max:100'],
            'invoice_footer_contact_label' => ['required', 'string', 'max:100'],
            'invoice_description_label' => ['required', 'string', 'max:100'],
            'invoice_quantity_label' => ['required', 'string', 'max:100'],
            'invoice_unit_price_label' => ['required', 'string', 'max:100'],
            'invoice_amount_label' => ['required', 'string', 'max:100'],
            'invoice_footer_note' => ['nullable', 'string', 'max:255'],
        ]);

        $data['invoice_show_signature'] = $request->boolean('invoice_show_signature');

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        if ($request->hasFile('invoice_background')) {
            $data['invoice_background_path'] = $request->file('invoice_background')->store('invoice-backgrounds', 'public');
        }

        $settings->update($data);
        $this->activityService->log('modification_parametres', $settings);

        return redirect()->route('settings.edit')->with('success', 'Paramètres mis à jour.');
    }
}
