<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
// use App\Http\Controllers\CertificateController; // <- si ya tienes este controlador

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación (JWT)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    // Públicas
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    // Protegidas con JWT
    Route::middleware('auth:api')->group(function () {
        Route::get('me',       [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('logout',  [AuthController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| Rutas de tu API
|--------------------------------------------------------------------------
*/

// Ejemplos de prueba
Route::get('ping', fn () => response()->json(['ok' => true])); // pública
Route::middleware('auth:api')->get('secure-ping', fn () => response()->json([
    'ok'   => true,
    'user' => auth('api')->user(),
]));

// --- Ejemplo para tus certificados caninos ---
// Pública (consulta por chip)
// Route::get('certificates/{chip}', [CertificateController::class, 'publicShow']);

// Privadas (gestión CRUD)
// Route::middleware('auth:api')->prefix('certificates')->group(function () {
//     Route::post('/',        [CertificateController::class, 'store']);
//     Route::put('{id}',      [CertificateController::class, 'update']);
//     Route::delete('{id}',   [CertificateController::class, 'destroy']);
//     Route::get('mine',      [CertificateController::class, 'indexByOwner']); // ejemplo
// });