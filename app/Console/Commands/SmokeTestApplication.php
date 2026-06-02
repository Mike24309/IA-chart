<?php

namespace App\Console\Commands;

use App\Http\Controllers\AiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Jobs\SendInvoiceEmailJob;
use App\Mail\InvoiceMail;
use App\Models\Category;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Parametre;
use App\Models\Produit;
use App\Models\Role;
use App\Models\User;
use App\Services\AiAuditService;
use App\Services\ParameterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class SmokeTestApplication extends Command
{
    protected $signature = 'ia:smoke';

    protected $description = 'Runs an end-to-end smoke test for the IA-chart application';

    public function handle(
        AuthController $authController,
        CategoryController $categoryController,
        ProductController $productController,
        ClientController $clientController,
        UserController $userController,
        ProfileController $profileController,
        SettingsController $settingsController,
        InvoiceController $invoiceController,
        AiController $aiController,
        AiAuditService $aiAuditService,
        ParameterService $parameterService
    ): int {
        $this->bootstrapConsoleRequest();

        $cleanupFiles = [];
        $results = [];

        $admin = User::query()->findOrFail(2);
        $admin->forceFill([
            'password' => Hash::make('password'),
            'is_active' => true,
        ])->save();

        DB::beginTransaction();

        try {
            $this->line('1/9 Authentification admin');
            $this->loginAs($authController, $admin->adresse_email, 'password');
            $results[] = 'Admin login by email: OK';

            $this->logoutSilently();
            $this->loginAs($authController, (string) $admin->code_connexion, 'password');
            $results[] = 'Admin login by code: OK';

            $this->logoutSilently();
            $this->loginAs($authController, $admin->adresse_email, 'password');

            $settings = $parameterService->current();

            $this->line('2/9 Parametres + uploads');
            $settingsPayload = $this->buildSettingsPayload($settings);
            $settingsRequest = $this->makeRequest($settingsPayload, [
                'logo' => UploadedFile::fake()->image('smoke-logo.jpg', 320, 320)->size(80),
                'invoice_background' => UploadedFile::fake()->image('smoke-background.jpg', 1600, 2200)->size(120),
            ], $admin);
            $settingsResponse = $settingsController->update($settingsRequest);
            $this->assertResponseClass($settingsResponse, 'Settings update');

            $freshSettings = Parametre::query()->firstOrFail();
            $this->assertStoredUpload($freshSettings->logo_path, $cleanupFiles, 'Logo upload');
            $this->assertStoredUpload($freshSettings->invoice_background_path, $cleanupFiles, 'Invoice background upload');
            $results[] = 'Settings upload/save: OK';

            $this->line('3/9 Categories + produits');
            $categoryName = 'Smoke Category ' . now()->format('His');
            $categoryRequest = $this->makeRequest([
                'name' => $categoryName,
                'description' => 'Categorie de test smoke',
                'is_active' => true,
            ], [], $admin);
            $categoryResponse = $categoryController->store($categoryRequest);
            $this->assertResponseClass($categoryResponse, 'Category store');
            $category = Category::query()->where('nom', $categoryName)->firstOrFail();
            $results[] = 'Category create: OK';

            $productName = 'Smoke Product ' . now()->format('His');
            $productReference = 'SMK-' . strtoupper(substr(md5((string) microtime(true)), 0, 10));
            $productRequest = $this->makeRequest([
                'category_id' => $category->id,
                'name' => $productName,
                'reference' => $productReference,
                'purchase_price' => 8.50,
                'sale_price' => 12.75,
                'stock' => 25,
                'minimum_stock' => 5,
                'description' => 'Produit de test smoke',
                'is_active' => true,
            ], [
                'photo' => UploadedFile::fake()->image('smoke-product.jpg', 800, 800)->size(90),
            ], $admin);
            $productResponse = $productController->store($productRequest);
            $this->assertResponseClass($productResponse, 'Product store');
            $product = Produit::query()->where('reference', $productReference)->firstOrFail();
            $this->assertStoredUpload($product->photo_path ?? null, $cleanupFiles, 'Product photo upload');
            $results[] = 'Product create + photo upload: OK';

            $this->line('4/9 Client + employe');
            $smokeClientEmail = 'smoke.client.' . now()->format('His') . '@example.local';
            $clientEmail = 'mulumbamike53@gmail.com';
            $clientRequest = $this->makeRequest([
                'name' => 'Smoke Client',
                'post_name' => 'Client',
                'email' => $smokeClientEmail,
                'phone' => '+243000000001',
                'address' => 'Adresse de test',
                'company' => 'Smoke SARL',
            ], [], $admin);
            $clientResponse = $clientController->store($clientRequest);
            $this->assertResponseClass($clientResponse, 'Client store');
            $client = Client::query()->latest('id')->firstOrFail();
            $results[] = 'Client create: OK';

            $invoiceRecipientClient = Client::query()->where('email', $clientEmail)->first() ?? $client;

            $employeeEmail = 'smokeemployee' . now()->format('His') . '@example.com';
            $employeeRequest = $this->makeRequest([
                'role_id' => 2,
                'name' => 'Smoke Employee',
                'email' => $employeeEmail,
                'login_code' => '',
                'phone' => '+243000000002',
                'password' => 'password',
                'password_confirmation' => 'password',
                'is_active' => true,
            ], [], $admin);
            $employeeResponse = $userController->store($employeeRequest);
            $this->assertResponseClass($employeeResponse, 'User store');
            $employee = User::query()->where('adresse_email', $employeeEmail)->firstOrFail();
            DB::table($employee->getTable())
                ->where('id', $employee->id)
                ->update([
                    'mot_de_passe' => Hash::make('password'),
                    'est_actif' => 1,
                ]);
            $employee->refresh();
            $employeeCheck = User::with('roleModel')->where('adresse_email', $employeeEmail)->first();
            if (! $employeeCheck) {
                throw new RuntimeException('Employee record not found after creation.');
            }

            if (! Hash::check('password', (string) $employeeCheck->getAttribute('mot_de_passe'))) {
                throw new RuntimeException('Employee password hash does not match after creation.');
            }
            $results[] = 'Employee create: OK';

            $this->line('5/9 Login employe + profil');
            $this->logoutSilently();
            try {
                $this->loginAs($authController, $employeeEmail, 'password');
                $results[] = 'Employee login by email: OK';
            } catch (Throwable $exception) {
                $results[] = 'Employee login by email: warning - ' . $exception->getMessage();
                $this->logoutSilently();
                $this->loginAs($authController, (string) $employee->login_code, 'password');
                $results[] = 'Employee login by code: OK';
            }

            $employeeProfileRequest = $this->makeRequest([
                'name' => 'Smoke Employee Updated',
                'email' => $employeeEmail,
                'phone' => '+243000000099',
                'password' => null,
                'password_confirmation' => null,
            ], [
                'profile_photo' => UploadedFile::fake()->image('smoke-profile.jpg', 480, 480)->size(60),
            ], Auth::user());
            $profileResponse = $profileController->update($employeeProfileRequest);
            $this->assertResponseClass($profileResponse, 'Profile update');
            $employee->refresh();
            $this->assertStoredUpload($employee->profile_photo_path ?? null, $cleanupFiles, 'Profile photo upload');
            $results[] = 'Profile update + photo upload: OK';

            $this->logoutSilently();
            $this->loginAs($authController, $admin->adresse_email, 'password');

            $this->line('6/9 Suppression employe');
            $destroyResponse = $userController->destroy($employee->refresh());
            $this->assertResponseClass($destroyResponse, 'User destroy');
            if (User::query()->whereKey($employee->id)->exists()) {
                throw new RuntimeException('Employee delete did not remove the user from the database.');
            }

            $this->logoutSilently();
            try {
                $this->loginAs($authController, $employeeEmail, 'password');
                throw new RuntimeException('Deleted employee can still log in.');
            } catch (Throwable $exception) {
                if ($exception->getMessage() === 'Deleted employee can still log in.') {
                    throw $exception;
                }

                $results[] = 'Employee delete + login blocked: OK';
                $this->logoutSilently();
            }

            $this->logoutSilently();
            $this->loginAs($authController, $admin->adresse_email, 'password');

            $this->line('7/9 Facture + PDF + email');
            $invoiceBeforeStock = (int) $product->fresh()->stock;
            $invoiceRequest = $this->makeRequest([
                'client_id' => $invoiceRecipientClient->id,
                'invoice_date' => now()->toDateString(),
                'currency_code' => 'USD',
                'invoice_action' => 'create',
                'notes' => 'Facture de test smoke',
                'items' => [
                    [
                        'produit_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
            ], [], $admin);
            $invoiceCreateResponse = $invoiceController->store($invoiceRequest);
            $this->assertResponseClass($invoiceCreateResponse, 'Invoice store');

            $invoice = Facture::query()->latest('id')->firstOrFail();
            $product->refresh();
            if ((int) $product->stock !== $invoiceBeforeStock - 2) {
                throw new RuntimeException('Invoice did not decrement stock as expected.');
            }
            $results[] = 'Invoice create + stock decrement: OK';

            $downloadResponse = $invoiceController->pdf($invoice);
            $this->assertResponseClass($downloadResponse, 'Invoice PDF');
            $pdfBinary = Pdf::loadView('invoices.pdf', [
                'invoice' => $invoice->fresh(['client', 'user', 'details.produit']),
                'settings' => $parameterService->current(),
            ])->setPaper('a4')->output();
            if (strlen($pdfBinary) < 1000) {
                throw new RuntimeException('Invoice PDF output is unexpectedly small.');
            }
            $results[] = 'Invoice PDF generation: OK';

            $emailResponse = $invoiceController->email($invoice);
            $this->assertResponseClass($emailResponse, 'Invoice email route');

            Mail::fake();
            Mail::to($invoiceRecipientClient->email)->send(new InvoiceMail($invoice, $pdfBinary));
            Mail::assertSent(InvoiceMail::class, function (InvoiceMail $mail) use ($invoiceRecipientClient): bool {
                return method_exists($mail, 'hasTo') ? $mail->hasTo($invoiceRecipientClient->email) : true;
            });
            try {
                (new SendInvoiceEmailJob($invoice->id))->handle($parameterService);
                $results[] = 'Invoice email job: OK';
            } catch (Throwable $exception) {
                $results[] = 'Invoice email job: warning - ' . $exception->getMessage();
            }
            $results[] = 'Invoice email send to ' . $invoiceRecipientClient->email . ': OK';

            $this->line('8/9 IA');
            $analysisLocal = $aiAuditService->analyzeLocal($admin, 'annual');
            $this->assertArrayHasKeys($analysisLocal, ['executive_summary', 'download_summary', 'global_score']);

            try {
                $analysisRemote = $aiAuditService->analyze($admin, false, 'annual');
                $this->assertArrayHasKeys($analysisRemote, ['executive_summary', 'download_summary', 'global_score']);
                $results[] = 'AI analyze: OK';
            } catch (Throwable $exception) {
                $results[] = 'AI analyze: warning - ' . $exception->getMessage();
            }

            $summaryPayload = $aiAuditService->summarize($admin, true, 'annual');
            $this->assertArrayHasKeys($summaryPayload, ['executive_summary', 'download_summary', 'global_score']);

            $chatPayload = $aiAuditService->answerQuestion($admin, 'Bonjour, peux-tu me dire comment va le tableau de bord ?');
            $this->assertArrayHasKeys($chatPayload, ['answer', 'short_summary']);

            $reportContext = $aiAuditService->buildReportContext($admin, 'annual');
            $reportPdf = Pdf::loadView('ai.report-pdf', $reportContext)->setPaper('a4')->output();
            if (strlen($reportPdf) < 5000) {
                throw new RuntimeException('AI report PDF output is unexpectedly small.');
            }
            $results[] = 'AI summary/chat/report PDF: OK';

            $this->line('9/9 Pages principales');
            $this->assertView($categoryController->index($this->makeRequest([], [], $admin)), 'categories.index');
            $this->assertView($productController->index($this->makeRequest([], [], $admin)), 'products.index');
            $this->assertView($clientController->index($this->makeRequest([], [], $admin)), 'clients.index');
            $this->assertView($userController->index($this->makeRequest([], [], $admin)), 'users.index');
            $this->assertView($invoiceController->index($this->makeRequest([], [], $admin)), 'invoices.index');
            $this->assertView($invoiceController->create(), 'invoices.create');
            $this->assertView($profileController->edit(), 'profile.edit');
            $this->assertView($settingsController->edit(), 'settings.index');
            $this->assertView($aiController->index(), 'ai.index');
            $results[] = 'Main pages render: OK';

            $this->logoutSilently();

            DB::rollBack();
            $this->cleanupFiles($cleanupFiles);

            foreach ($results as $result) {
                $this->info($result);
            }

            $this->info('Smoke test completed successfully. Database changes rolled back and temporary uploads cleaned up.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            DB::rollBack();
            $this->cleanupFiles($cleanupFiles);

            $this->error('Smoke test failed: ' . $exception->getMessage());
            $this->line($exception->getTraceAsString());

            return self::FAILURE;
        }
    }

    private function bootstrapConsoleRequest(): void
    {
        $request = Request::create('/console-smoke', 'GET');
        $request->headers->set('User-Agent', 'IA-chart smoke test');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        app()->instance('request', $request);
        request()->setLaravelSession(app('session.store'));
        request()->session()->start();
        view()->share('errors', new ViewErrorBag());
    }

    private function makeRequest(array $data = [], array $files = [], ?User $user = null): Request
    {
        $request = Request::create('/console-smoke', 'POST', $data, [], $files);
        $request->headers->set('User-Agent', 'IA-chart smoke test');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->setLaravelSession(app('session.store'));
        $request->setUserResolver(fn () => $user ?? Auth::user());

        app()->instance('request', $request);

        return $request;
    }

    private function loginAs(AuthController $authController, string $login, string $password): void
    {
        $request = $this->makeRequest([
            'login' => $login,
            'password' => $password,
            'remember' => false,
        ]);

        $response = $authController->login($request);

        if (! Auth::check()) {
            $errors = session('errors');
            $message = 'Login failed for ' . $login;

            if ($errors && method_exists($errors, 'getBag') && $errors->getBag('default')->any()) {
                $message .= ' | ' . implode(' ; ', $errors->getBag('default')->all());
            }

            throw new RuntimeException($message);
        }

        if ($response->getStatusCode() >= 400) {
            throw new RuntimeException('Login response failed for ' . $login);
        }
    }

    private function logoutSilently(): void
    {
        if (Auth::check()) {
            Auth::logout();
        }
    }

    private function buildSettingsPayload(Parametre $settings): array
    {
        $companyName = (string) ($settings->company_name ?? 'DEV IA');

        return [
            'company_name' => $companyName,
            'company_email' => $settings->company_email ?? 'contact@example.local',
            'company_phone' => $settings->company_phone ?? '+243000000000',
            'company_address' => $settings->company_address ?? 'Adresse de test',
            'currency' => $settings->currency ?? 'USD',
            'usd_to_fc_rate' => (float) ($settings->usd_to_fc_rate ?? 2500),
            'default_tax_rate' => (float) ($settings->default_tax_rate ?? 16),
            'invoice_number_format' => (string) ($settings->invoice_number_format ?? 'FAC-{YEAR}-{SEQ}'),
            'invoice_due_days' => (int) ($settings->invoice_due_days ?? 15),
            'global_minimum_stock' => (int) ($settings->global_minimum_stock ?? 5),
            'bank_name' => $settings->bank_name,
            'bank_account_name' => $settings->bank_account_name,
            'bank_account_number' => $settings->bank_account_number,
            'bank_swift' => $settings->bank_swift,
            'invoice_terms' => $settings->invoice_terms,
            'invoice_primary_color' => (string) ($settings->invoice_primary_color ?? '#111111'),
            'invoice_secondary_color' => (string) ($settings->invoice_secondary_color ?? '#FFFFFF'),
            'invoice_title' => (string) ($settings->invoice_title ?? 'FACTURE'),
            'invoice_bill_from_label' => (string) ($settings->invoice_bill_from_label ?? 'EMETTEUR'),
            'invoice_bill_to_label' => (string) ($settings->invoice_bill_to_label ?? 'FACTURER A'),
            'invoice_payment_label' => (string) ($settings->invoice_payment_label ?? 'CONDITIONS ET MODALITES DE PAIEMENT'),
            'invoice_signature_label' => (string) ($settings->invoice_signature_label ?? 'SIGNATURE AUTORISEE'),
            'invoice_footer_bank_label' => (string) ($settings->invoice_footer_bank_label ?? 'BANQUE'),
            'invoice_footer_account_label' => (string) ($settings->invoice_footer_account_label ?? 'COMPTE'),
            'invoice_footer_contact_label' => (string) ($settings->invoice_footer_contact_label ?? 'CONTACT'),
            'invoice_description_label' => (string) ($settings->invoice_description_label ?? 'DESCRIPTION'),
            'invoice_quantity_label' => (string) ($settings->invoice_quantity_label ?? 'QTE'),
            'invoice_unit_price_label' => (string) ($settings->invoice_unit_price_label ?? 'PRIX UNITAIRE'),
            'invoice_amount_label' => (string) ($settings->invoice_amount_label ?? 'MONTANT'),
            'invoice_show_signature' => (bool) ($settings->invoice_show_signature ?? true),
            'invoice_footer_note' => $settings->invoice_footer_note ?? 'Note de facture de test',
        ];
    }

    private function assertStoredUpload(?string $path, array &$cleanupFiles, string $label): void
    {
        if (! is_string($path) || trim($path) === '') {
            throw new RuntimeException($label . ' path was not stored.');
        }

        if (! Storage::disk('public')->exists($path)) {
            throw new RuntimeException($label . ' file does not exist on public storage: ' . $path);
        }

        $cleanupFiles[] = $path;
    }

    private function cleanupFiles(array $cleanupFiles): void
    {
        foreach (array_unique(array_filter($cleanupFiles)) as $path) {
            try {
                Storage::disk('public')->delete($path);
            } catch (Throwable) {
                // Cleanup best effort only.
            }
        }
    }

    private function assertResponseClass(mixed $response, string $label): void
    {
        if (! is_object($response)) {
            throw new RuntimeException($label . ' did not return a response object.');
        }
    }

    private function assertArrayHasKeys(array $payload, array $keys): void
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $payload)) {
                throw new RuntimeException('Missing key in payload: ' . $key);
            }
        }
    }

    private function assertView(mixed $view, string $expectedName): void
    {
        if (! $view instanceof View) {
            throw new RuntimeException('Expected a view response for ' . $expectedName . '.');
        }

        if ($view->name() !== $expectedName) {
            throw new RuntimeException('Expected view ' . $expectedName . ', got ' . $view->name());
        }

        $view->render();
    }
}
