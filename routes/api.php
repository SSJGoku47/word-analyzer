<?php

use App\Http\Controllers\WordFrequencyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/word-frequency', [WordFrequencyController::class, 'analyze']);