<?php

use App\Http\Controllers\RegressionTestController;
use App\Http\Controllers\TestGenerationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', [TestGenerationController::class, 'index'])
    ->name('testmate.index');

Route::post('/generate-tests', [TestGenerationController::class, 'generate'])
    ->name('testmate.generate');

Route::get('/regression', [RegressionTestController::class, 'index'])
    ->name('regression.index');

Route::post('/regression/analyze', [RegressionTestController::class, 'analyze'])
    ->name('regression.analyze');
