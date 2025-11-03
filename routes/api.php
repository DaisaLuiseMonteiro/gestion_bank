<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompteBloqueController;

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
    // Toutes les routes sont accessibles sans authentification
    Route::get('comptes', [CompteController::class, 'index']);
    Route::get('comptes/numero/{numeroCompte}', [CompteController::class, 'showByNumero']);
    Route::get('clients/{clientId}/comptes', [CompteController::class, 'byClient']);
    Route::get('comptes/{compteId}', [CompteController::class, 'show']);
    Route::patch('comptes/{compteId}', [CompteController::class, 'update']);
    Route::delete('comptes/{compteId}', [CompteController::class, 'destroy']);
    Route::post('comptes/{compteId}/bloquer', [CompteBloqueController::class, 'bloquer']);
    Route::get('clients/telephone/{telephone}', [ClientController::class, 'showByPhone']);
    Route::get('clients', [ClientController::class, 'index']);
});
