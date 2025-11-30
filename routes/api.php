<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PrintApiController;
use App\Http\Controllers\Api\WebhookController;

Route::middleware(['printagent.token'])->group(function () {
    Route::get('/print/next', [PrintApiController::class, 'next']);
    Route::post('/print/{id}/ack', [PrintApiController::class, 'ack']);
});

// Webhooks de plataformas (sem autenticação para receber notificações externas)
Route::post('/webhooks/mercado-livre', [WebhookController::class, 'mercadoLivre'])->name('api.webhooks.mercado-livre');
Route::post('/webhooks/shopee', [WebhookController::class, 'shopee'])->name('api.webhooks.shopee');
Route::post('/webhooks/shopify', [WebhookController::class, 'shopify'])->name('api.webhooks.shopify');
