<?php

use App\Http\Controllers\DiagnoseController;
use Illuminate\Support\Facades\Route;

Route::get('/', DiagnoseController::class)->name('home');
