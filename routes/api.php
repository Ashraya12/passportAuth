<?php

use App\Http\Controllers\Api\AuthController as ApiAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::post('/register', [ApiAuthController::class, 'register']);
Route::post('/login', [ApiAuthController::class, 'login']);
Route::post('/refresh-token', [ApiAuthController::class, 'refreshToken']);

// Route::middleware(['auth:api', 'scope:admin'])->group(function () {
//     Route::get('/admin', function () {
//         return response()->json(['message' => 'Welcome, Admin!']);
//     });

//     Route::post('/logout', [ApiAuthController::class, 'logout']);
// });

// Route::middleware(['auth:api', 'scope:super_admin'])->group(function () {
//     Route::get('/super-admin', function () {
//         return response()->json(['message' => 'Welcome, Super Admin!']);
//     });
// });

Route::middleware('auth:api')->group(function () {

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/super-admin', function () {
            return response()->json(['message' => 'Welcome, Super Admin!']);
        });
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin', function () {
            return response()->json(['message' => 'Welcome, Admin!']);
        });
    });

    Route::get('/user', function () {
        return response()->json(['message' => 'Welcome, User!']);
    });

    Route::post('/logout', [ApiAuthController::class, 'logout']);
});
