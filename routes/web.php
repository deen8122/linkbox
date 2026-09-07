<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LinkBlockController;
use App\Http\Controllers\LinkBlockGroupController;
use App\Http\Controllers\LinkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});
Route::get('/login', function () {
    return view('home');
})->name('login');
Route::post('/auth/request-code', [
    AuthController::class,
    'requestCode',
]);

Route::post('/auth/verify-code', [
    AuthController::class,
    'verifyCode',
]);

Route::middleware('auth')->group(function () {
    Route::put('/api/link-blocks/reorder', [LinkBlockController::class, 'reorder',]);
    Route::get('/api/links', [LinkController::class, 'index']);
    Route::post('/api/links', [LinkController::class, 'store']);
    Route::put('/api/links/{link}', [LinkController::class, 'update']);
    Route::delete('/api/links/{link}', [LinkController::class, 'destroy']);


    Route::get('/api/link-blocks', [LinkBlockController::class, 'index']);
    Route::post('/api/link-blocks', [LinkBlockController::class, 'store']);
    Route::put('/api/link-blocks/{linkBlock}', [LinkBlockController::class, 'update',]);
    Route::delete('/api/link-blocks/{linkBlock}', [LinkBlockController::class, 'destroy']);

    Route::put('/api/link-block-groups/reorder', [LinkBlockGroupController::class, 'reorder']);
    Route::get('/api/link-block-groups', [LinkBlockGroupController::class, 'index']);
    Route::post('/api/link-block-groups', [LinkBlockGroupController::class, 'store']);
    Route::put('/api/link-block-groups/{linkBlockGroup}', [LinkBlockGroupController::class, 'update']);
    Route::delete('/api/link-block-groups/{linkBlockGroup}', [LinkBlockGroupController::class, 'destroy']);

    Route::post('/auth/logout', [
        AuthController::class,
        'logout',
    ]);

    Route::get('/auth/user', [
        AuthController::class,
        'user',
    ]);

});
