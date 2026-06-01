<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\UserController;
use App\Jobs\SendInvoiceEmailJob;
use App\Mail\InvoiceMail;
use App\Models\Category;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Produit;
use App\Models\Role;
use App\Models\User;
use App\Services\AiAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

$results = [];
$prefix = 'QA-' . now()->format('YmdHis');

$record = function (string $name, bool $ok, array $extra = []) use (&$results): void {
    $results[] = array_merge([
        'test' => $name,
        'ok' => $ok,
    ], $extra);
};

$makeRequest = function (string $method, array $data, User $user): Request {
    $request = Request::create('/', $method, $data);
    $request->setUserResolver(fn () => $user);

    return $request;
};

try {
    Role::firstOrCreate(['nom' => 'admin'], ['libelle' => 'Administrateur']);
    Role::firstOrCreate(['nom' => 'employee'], ['libelle' => 'Employe']);

    $adminRole = Role::where('nom', 'admin')->firstOrFail();
    $employeeRole = Role::where('nom', 'employee')->firstOrFail();
    $record('roles_ready', true, [
        'admin_role_id' => $adminRole->id,
        'employee_role_id' => $employeeRole->id,
    ]);

    $admin = User::create([
        'role_id' => $adminRole->id,
        'name' => "{$prefix} Admin",
        'email' => strtolower("{$prefix}.admin@example.com"),
        'login_code' => "{$prefix}-ADM",
        'phone' => '000000001',
        'password' => 'secret123',
        'is_active' => true,
    ]);
    $record('admin_created', true, ['user_id' => $admin->id]);

    $userController = app(UserController::class);
    $employeeRequest = $makeRequest('POST', [
        'role_id' => $employeeRole->id,
        'name' => "{$prefix} Employe",
        'email' => strtolower("{$prefix}.employee@example.com"),
        'login_code' => "{$prefix}-EMP",
        'phone' => '000000002',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'is_active' => 1,
    ], $admin);
    $employeeResponse = $userController->store($employeeRequest);
    $employee = User::where('adresse_email', strtolower("{$prefix}.employee@example.com"))->first();
    $record('employee_created_via_controller', $employeeResponse->getStatusCode() === 302 && $employee !== null, [
        'response_status' => $employeeResponse->getStatusCode(),
        'employee_id' => $employee?->id,
    ]);

    $categoryController = app(CategoryController::class);
    $categoryRequest = $makeRequest('POST', [
        'name' => "{$prefix} Categorie",
        'description' => 'Categorie de test backend',
        'is_active' => 1,
    ], $admin);
    $categoryResponse = $categoryController->store($categoryRequest);
    $category = Category::where('nom', "{$prefix} Categorie")->first();
    $record('category_created_via_controller', $categoryResponse->getStatusCode() === 302 && $category !== null, [
        'response_status' => $categoryResponse->getStatusCode(),
        'category_id' => $category?->id,
    ]);

    $product = Produit::create([
        'category_id' => $category?->id,
        'name' => "{$prefix} Produit",
        'reference' => "{$prefix}-REF",
        'purchase_price' => 100,
        'sale_price' => 150,
        'stock' => 10,
        'minimum_stock' => 2,
        'description' => 'Produit de test backend',
        'is_active' => true,
    ]);
    $record('product_created', $product->exists, [
        'product_id' => $product->id,
        'initial_stock' => $product->stock,
    ]);

    $stockMovementController = app(StockMovementController::class);
    $movementRequest = $makeRequest('POST', [
        'produit_id' => $product->id,
        'movement_type' => 'entree',
        'quantity' => 5,
    ], $admin);
    $movementResponse = $stockMovementController->store($movementRequest);
    $product->refresh();
    $record('stock_entry_created_via_controller', $movementResponse->getStatusCode() === 302 && (int) $product->stock === 15, [
        'response_status' => $movementResponse->getStatusCode(),
        'stock_after_entry' => $product->stock,
    ]);

    $invoiceController = app(InvoiceController::class);
    $invoiceRequest = $makeRequest('POST', [
        'client_name' => "{$prefix} Client",
        'client_post_name' => 'Test',
        'client_email' => strtolower("{$prefix}.client@example.com"),
        'client_phone' => '000000003',
        'client_address' => 'Adresse QA',
        'client_company' => 'DEV QA',
        'invoice_date' => now()->toDateString(),
        'currency_code' => 'FC',
        'invoice_action' => 'create',
        'notes' => 'Note QA backend',
        'items' => [
            [
                'produit_id' => $product->id,
                'quantity' => 3,
                'unit_price_ht' => 200,
            ],
        ],
    ], $employee);
    $invoiceResponse = $invoiceController->store($invoiceRequest);
    $invoice = Facture::latest('id')->first();
    $product->refresh();
    $record('invoice_created_via_controller', $invoiceResponse->getStatusCode() === 302 && $invoice !== null, [
        'response_status' => $invoiceResponse->getStatusCode(),
        'invoice_id' => $invoice?->id,
        'invoice_currency' => $invoice?->invoiceCurrency(),
        'stock_after_invoice' => $product->stock,
    ]);
    $invoiceDetail = $invoice?->details()->latest('id')->first();
    $record('invoice_custom_unit_price_saved', $invoiceDetail !== null && (float) $invoiceDetail->unit_price_ht === 200.0, [
        'unit_price_ht' => $invoiceDetail?->unit_price_ht,
        'line_total_ht' => $invoiceDetail?->line_total_ht,
    ]);
    $record('invoice_custom_line_total_saved', $invoiceDetail !== null && (float) $invoiceDetail->line_total_ht === 600.0, [
        'expected_line_total' => 600.0,
        'actual_line_total' => $invoiceDetail?->line_total_ht,
    ]);
    $record('stock_decreased_after_invoice', (int) $product->stock === 12, [
        'expected_stock' => 12,
        'actual_stock' => $product->stock,
    ]);

    Auth::login($employee);
    $pdfResponse = $invoiceController->pdf($invoice);
    $record('invoice_pdf_generated', $pdfResponse->getStatusCode() === 200, [
        'response_status' => $pdfResponse->getStatusCode(),
    ]);
    Auth::logout();

    Mail::fake();
    $job = new SendInvoiceEmailJob($invoice->id);
    $job->handle(app(App\Services\ParameterService::class));
    Mail::assertSent(InvoiceMail::class);
    $record('invoice_email_job_builds_mail', true);

    $ai = app(AiAuditService::class);
    $localAnalysis = $ai->analyzeLocal($admin, 'annual');
    $record('ai_local_analysis', ($localAnalysis['analysis_origin'] ?? null) === 'local', [
        'origin' => $localAnalysis['analysis_origin'] ?? null,
    ]);

    $geminiAnalysis = $ai->analyze($admin, true, 'annual');
    $record('ai_gemini_summary', ($geminiAnalysis['analysis_origin'] ?? null) === 'ia' && trim((string) ($geminiAnalysis['executive_summary'] ?? '')) !== '', [
        'origin' => $geminiAnalysis['analysis_origin'] ?? null,
        'summary' => $geminiAnalysis['executive_summary'] ?? null,
    ]);

    $question = $ai->answerQuestion($admin, 'Fait un resume simple de mes ventes');
    $record('ai_question_answer', ($question['analysis_origin'] ?? null) === 'ia' && trim((string) ($question['answer'] ?? '')) !== '', [
        'origin' => $question['analysis_origin'] ?? null,
        'answer' => $question['answer'] ?? null,
    ]);

    Auth::login($admin);
    $deleteInvoiceResponse = $invoiceController->destroy($invoice->fresh());
    $product->refresh();
    $record('invoice_deleted_and_stock_restored', $deleteInvoiceResponse->getStatusCode() === 302 && (int) $product->stock === 15, [
        'response_status' => $deleteInvoiceResponse->getStatusCode(),
        'stock_after_delete' => $product->stock,
    ]);
    Auth::logout();

    $employeeWithoutOps = User::create([
        'role_id' => $employeeRole->id,
        'name' => "{$prefix} Employe Supprimable",
        'email' => strtolower("{$prefix}.employee2@example.com"),
        'login_code' => "{$prefix}-EMP2",
        'phone' => '000000004',
        'password' => 'secret123',
        'is_active' => true,
    ]);
    Auth::login($admin);
    $deleteEmployeeResponse = $userController->destroy($employeeWithoutOps);
    $record('employee_deleted', $deleteEmployeeResponse->getStatusCode() === 302 && ! User::whereKey($employeeWithoutOps->id)->exists(), [
        'response_status' => $deleteEmployeeResponse->getStatusCode(),
    ]);
    Auth::logout();
} catch (Throwable $e) {
    $record('fatal_exception', false, [
        'message' => $e->getMessage(),
        'type' => get_class($e),
        'line' => $e->getLine(),
        'file' => $e->getFile(),
    ]);
}

echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
