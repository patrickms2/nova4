<?php

use App\Http\Controllers\RedsysController;
use Illuminate\Support\Facades\Route;

Route::post('/redsys/pay', [RedsysController::class, 'pay'])->name('redsys.pay');

// Pagar a partir de un registro Pago (Route Model Binding)
Route::get('/redsys/pay/{pago}', [RedsysController::class, 'formPago'])->name('redsys.pay.fromPago');

// Notificación servidor a servidor de Redsys
Route::post('/redsys/callback', [RedsysController::class, 'callback'])->name('redsys.callback');

// Retornos del usuario
Route::get('/redsys/ok', [RedsysController::class, 'ok'])->name('redsys.ok');
Route::get('/redsys/ko', [RedsysController::class, 'ko'])->name('redsys.ko');
