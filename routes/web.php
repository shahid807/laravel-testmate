<?php

use App\Http\Controllers\TestGenerationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', [TestGenerationController::class, 'index'])
    ->name('testmate.index');

Route::post('/generate-tests', [TestGenerationController::class, 'generate'])
    ->name('testmate.generate');
