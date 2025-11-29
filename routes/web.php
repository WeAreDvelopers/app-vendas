<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\ImportUIController;
use App\Http\Controllers\Panel\ProductUIController;
use App\Http\Controllers\Panel\ListingUIController;
use App\Http\Controllers\Panel\OrderUIController;
use App\Http\Controllers\Panel\SupplierController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\WebhookController;

// Authentication routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', fn() => redirect()->route('panel.dashboard'))->name('home');

// Mercado Livre OAuth Callback (sem autenticação para receber callback)
Route::get('/mercado-livre/callback', [\App\Http\Controllers\Panel\MercadoLivreController::class, 'callback'])->name('mercado-livre.callback');
Route::post('/mercado-livre/notifications', [\App\Http\Controllers\Panel\MercadoLivreController::class, 'notifications'])->name('mercado-livre.notifications');

Route::prefix('panel')->name('panel.')->middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Companies
    Route::get('/companies', [\App\Http\Controllers\Panel\CompanyController::class, 'index'])->name('companies.index');
    Route::post('/companies/switch', [\App\Http\Controllers\Panel\CompanyController::class, 'switch'])->name('companies.switch');
    Route::get('/companies/create', [\App\Http\Controllers\Panel\CompanyController::class, 'create'])->name('companies.create');
    Route::post('/companies', [\App\Http\Controllers\Panel\CompanyController::class, 'store'])->name('companies.store');
    Route::get('/companies/{id}/edit', [\App\Http\Controllers\Panel\CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('/companies/{id}', [\App\Http\Controllers\Panel\CompanyController::class, 'update'])->name('companies.update');

    // Integrations
    Route::get('/integrations', [\App\Http\Controllers\Panel\IntegrationController::class, 'index'])->name('integrations.index');

    // Mercado Livre Integration
    Route::get('/integrations/mercado-livre/connect', [\App\Http\Controllers\Panel\IntegrationController::class, 'mercadoLivreConnect'])->name('integrations.ml.connect');
    Route::get('/integrations/mercado-livre/callback', [\App\Http\Controllers\Panel\IntegrationController::class, 'mercadoLivreCallback'])->name('integrations.ml.callback');
    Route::post('/integrations/mercado-livre/disconnect', [\App\Http\Controllers\Panel\IntegrationController::class, 'mercadoLivreDisconnect'])->name('integrations.ml.disconnect');
    Route::post('/integrations/mercado-livre/reconnect', [\App\Http\Controllers\Panel\IntegrationController::class, 'mercadoLivreReconnect'])->name('integrations.ml.reconnect');

    // Google Drive Integration
    Route::get('/integrations/google-drive/connect', [\App\Http\Controllers\Panel\IntegrationController::class, 'googleDriveConnect'])->name('integrations.drive.connect');
    Route::get('/integrations/google-drive/callback', [\App\Http\Controllers\Panel\IntegrationController::class, 'googleDriveCallback'])->name('integrations.drive.callback');
    Route::post('/integrations/google-drive/disconnect', [\App\Http\Controllers\Panel\IntegrationController::class, 'googleDriveDisconnect'])->name('integrations.drive.disconnect');

    // Suppliers
    Route::resource('suppliers', SupplierController::class);
    Route::get('/suppliers/{supplier}/mapping', [SupplierController::class, 'editMapping'])->name('suppliers.mapping');
    Route::put('/suppliers/{supplier}/mapping', [SupplierController::class, 'updateMapping'])->name('suppliers.mapping.update');

    // Imports
    Route::get('/imports',           [ImportUIController::class, 'index'])->name('imports.index');
    Route::post('/imports',          [ImportUIController::class, 'store'])->name('imports.store');
    Route::get('/imports/{id}',      [ImportUIController::class, 'show'])->name('imports.show');
    Route::delete('/imports/{id}',   [ImportUIController::class, 'destroy'])->name('imports.destroy');
    Route::post('/imports/{id}/process', [ImportUIController::class, 'processProducts'])->name('imports.process');
    Route::get('/imports/{id}/errors', [ImportUIController::class, 'errors'])->name('imports.errors');
    Route::get('/imports/{id}/errors/export', [ImportUIController::class, 'exportErrors'])->name('imports.errors.export');
    Route::delete('/imports/{importId}/items/{itemId}', [ImportUIController::class, 'destroyItem'])->name('imports.items.destroy');

    // Products, Listings, Orders
    Route::get('/products',          [ProductUIController::class, 'index'])->name('products.index');
    Route::get('/products/{id}',     [ProductUIController::class, 'show'])->name('products.show');
    Route::get('/products/{id}/edit', [ProductUIController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}',     [ProductUIController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}',  [ProductUIController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/{id}/regenerate-description', [ProductUIController::class, 'regenerateDescription'])->name('products.regenerate-description');
    Route::post('/products/{id}/images', [ProductUIController::class, 'uploadImages'])->name('products.images.upload');
    Route::post('/products/{id}/images/search', [ProductUIController::class, 'searchImages'])->name('products.images.search');
    Route::post('/products/{id}/images/download', [ProductUIController::class, 'downloadSelectedImages'])->name('products.images.download');
    Route::post('/products/{id}/images/drive/download', [ProductUIController::class, 'downloadDriveImages'])->name('products.images.drive.download');
    Route::delete('/products/{id}/images/{imageId}', [ProductUIController::class, 'deleteImage'])->name('products.images.delete');
    Route::delete('/products/{id}/images', [ProductUIController::class, 'deleteAllImages'])->name('products.images.deleteAll');
    Route::post('/products/{id}/reference-image', [ProductUIController::class, 'uploadReferenceImage'])->name('products.reference-image.upload');
    Route::delete('/products/{id}/reference-image', [ProductUIController::class, 'deleteReferenceImage'])->name('products.reference-image.delete');

    // Mercado Livre - Autenticação
    Route::get('/mercado-livre/connect', [\App\Http\Controllers\Panel\MercadoLivreController::class, 'connect'])->name('mercado-livre.connect');
    Route::get('/mercado-livre/disconnect', [\App\Http\Controllers\Panel\MercadoLivreController::class, 'disconnect'])->name('mercado-livre.disconnect');
    Route::get('/mercado-livre/status', [\App\Http\Controllers\Panel\MercadoLivreController::class, 'status'])->name('mercado-livre.status');

    // Mercado Livre - Produtos
    Route::get('/mercado-livre/{productId}/prepare', [\App\Http\Controllers\Panel\MercadoLivreController::class, 'prepare'])->name('mercado-livre.prepare');
    Route::post('/mercado-livre/{productId}/draft', [\App\Http\Controllers\Panel\MercadoLivreController::class, 'saveDraft'])->name('mercado-livre.save-draft');
    Route::post('/mercado-livre/{productId}/publish', [\App\Http\Controllers\Panel\MercadoLivreController::class, 'publish'])->name('mercado-livre.publish');
    Route::post('/mercado-livre/{productId}/save-and-publish', [\App\Http\Controllers\Panel\MercadoLivreController::class, 'saveDraftAndPublish'])->name('mercado-livre.save-and-publish');
    Route::get('/mercado-livre/{productId}/publish-status', [\App\Http\Controllers\Panel\MercadoLivreController::class, 'checkPublishStatus'])->name('mercado-livre.publish-status');
    Route::get('/mercado-livre/category-attributes', [\App\Http\Controllers\Panel\MercadoLivreController::class, 'getCategoryAttributes'])->name('mercado-livre.category-attributes');

    Route::get('/listings',          [ListingUIController::class, 'index'])->name('listings.index');
    Route::get('/orders',            [OrderUIController::class, 'index'])->name('orders.index');

    // Monitor
    Route::get('/monitor/queues',    [MonitorController::class, 'index'])->name('monitor.queues');

    // Notifications API
    Route::get('/notifications',              [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read',   [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all',    [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{id}',      [NotificationController::class, 'destroy'])->name('notifications.destroy');
});

Route::post('/import/supplier', [ImportController::class,'store']);
Route::post('/import/convert-without-ai', [ImportController::class,'convertWithoutAI']);
Route::post('/webhooks/meli',   [WebhookController::class,'meli']); 
