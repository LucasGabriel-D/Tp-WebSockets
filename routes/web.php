<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\RemoteStyleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GameController::class, 'lobby'])->name('lobby');
Route::post('/entrar', [GameController::class, 'enter'])->name('enter');

Route::middleware('auth')->group(function () {
    Route::post('/salas', [GameController::class, 'create'])->name('rooms.create');
    Route::post('/salas/unirse', [GameController::class, 'join'])->name('rooms.join');
    Route::get('/sala/{code}', [GameController::class, 'room'])->name('room');
    Route::post('/sala/{code}/resultado', [GameController::class, 'result'])->name('rooms.result');
});

// Trabajo guiado: emisor -> evento RemoteStyleUpdate -> canal público "style-channel" -> receptores
Route::get('/emisor', [RemoteStyleController::class, 'emisor'])->name('emisor');
Route::post('/emitir', [RemoteStyleController::class, 'emitir'])->middleware('throttle:30,1')->name('emitir');
Route::get('/receptor', [RemoteStyleController::class, 'receptor'])->name('receptor');
