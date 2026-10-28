<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatosAgrupadosController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/datos-agrupados', [DatosAgrupadosController::class, 'index'])->name('agrupados');
