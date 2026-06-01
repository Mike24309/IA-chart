<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AiSettingsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

// Ce groupe contient uniquement les routes accessibles avant authentification.
Route::middleware('guest')->group(function (): void {
    // Cette route affiche la page de connexion.
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    // Cette route traite la tentative de connexion.
    Route::post('/login', [AuthController::class, 'login'])->name('login.perform');
    // Cette route permet l inscription libre d un compte employe.
    Route::post('/register', [AuthController::class, 'register'])->name('register.perform');
    // Ces routes gerent le parcours mot de passe oublie.
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Ce groupe protege toutes les routes qui exigent un utilisateur connecte et actif.
Route::middleware(['auth', 'active'])->group(function (): void {
    // Cette route redirige simplement vers le dashboard principal.
    Route::get('/home', fn () => redirect()->route('dashboard'))->name('home');
    // Cette route ferme la session de l utilisateur.
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    // Cette route affiche le tableau de bord selon le role.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Ces routes correspondent aux fonctions accessibles aux comptes connectes.
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])
        ->name('invoices.show')
        ->missing(fn () => redirect()->route('invoices.index')->withErrors(['invoice' => 'Cette facture n existe plus.']));
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])
        ->name('invoices.pdf')
        ->missing(fn () => redirect()->route('invoices.index')->withErrors(['invoice' => 'Cette facture n existe plus.']));
    Route::post('/invoices/{invoice}/email', [InvoiceController::class, 'email'])
        ->name('invoices.email')
        ->missing(fn () => redirect()->route('invoices.index')->withErrors(['invoice' => 'Cette facture n existe plus.']));

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Ce sous-groupe reserve les actions sensibles a l administrateur.
    Route::middleware('role:admin')->group(function (): void {
        // La suppression de facture est limitee a l administrateur pour proteger la coherence du stock.
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])
            ->name('invoices.destroy')
            ->missing(fn () => redirect()->route('invoices.index')->withErrors(['invoice' => 'Cette facture a deja ete supprimee.']));
        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('products', ProductController::class)
            ->except(['show', 'index'])
            ->missing(fn () => redirect()->route('products.index')->withErrors(['product' => 'Ce produit n existe plus.']));
        Route::resource('clients', ClientController::class)->except('show');
        Route::get('/stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');
        Route::get('/stock-movements/create', [StockMovementController::class, 'create'])->name('stock-movements.create');
        Route::post('/stock-movements', [StockMovementController::class, 'store'])->name('stock-movements.store');
        Route::resource('users', UserController::class)->except('show');
        Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
        Route::get('/ai', [AiController::class, 'index'])->name('ai.index');
        Route::post('/ai/analyze', [AiController::class, 'analyze'])->name('ai.analyze');
        Route::post('/ai/interpret', [AiController::class, 'interpret'])->name('ai.interpret');
        Route::post('/ai/interpret-page', [AiController::class, 'interpretPage'])->name('ai.interpret.page');
        Route::post('/ai/ask', [AiController::class, 'ask'])->name('ai.ask');
        Route::get('/ai/report', [AiController::class, 'report'])->name('ai.report');
        Route::post('/ai/notifications/read', [AiController::class, 'notificationsRead'])->name('ai.notifications.read');
        Route::get('/ai/settings', [AiSettingsController::class, 'edit'])->name('ai.settings.edit');
        Route::put('/ai/settings', [AiSettingsController::class, 'update'])->name('ai.settings.update');
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});
