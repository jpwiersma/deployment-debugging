<?php

use App\Http\Controllers\DiagnoseController;
use Illuminate\Support\Facades\Route;

// Moved to /diagnostics because Statamic takes over front-end routing at /
Route::get('/diagnostics', DiagnoseController::class)->name('home');
