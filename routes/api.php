<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\ClientController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('monteiro.daisa/v1')->middleware('logging')->group(function () {
    // Comptes
    Route::get('comptes', [CompteController::class, 'index']);
    Route::get('clients/{clientId}/comptes', [CompteController::class, 'byClient']);
    Route::get('comptes/{compteId}', [CompteController::class, 'show']);
    Route::patch('comptes/{compteId}', [CompteController::class, 'update']); // Ajout de la route PATCH
    Route::delete('comptes/{compteId}', [CompteController::class, 'destroy']);

    // Clients
    Route::get('clients', [ClientController::class, 'index']);
});
