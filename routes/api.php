<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use Illuminate\Support\Facades\Route;

// RUTAS PÚBLICAS
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login',    [AuthController::class, 'login']);

// Certificados públicos - fuera del grupo auth
Route::get('certificates/{certificado}', [CertificateController::class, 'show']); // PÚBLICO
Route::get('certificates/code/{code}', [CertificateController::class, 'showByCode']);
// RUTAS PROTEGIDAS (requieren autenticación)
Route::middleware('auth:api')->group(function () {
    // Rutas de autenticación protegidas
    Route::prefix('auth')->group(function () {
        Route::get('me',       [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('logout',  [AuthController::class, 'logout']);
    });

    // Rutas de certificados protegidas
    Route::prefix('certificates')->group(function () {
        Route::get('/', [CertificateController::class, 'index']);   // list + búsqueda
        Route::post('/', [CertificateController::class, 'store']);
        Route::put('{certificado}', [CertificateController::class, 'update']);
        Route::delete('{id}', [CertificateController::class, 'destroy']);
    });
});