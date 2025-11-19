<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PrintApiController;

Route::middleware(['printagent.token'])->group(function () {
    Route::get('/print/next', [PrintApiController::class, 'next']);
    Route::post('/print/{id}/ack', [PrintApiController::class, 'ack']);
});
