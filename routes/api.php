<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductEntryController;

Route::get('/entries', [ProductEntryController::class, 'list']);
Route::post('/entries', [ProductEntryController::class, 'store']);
Route::put('/entries/{id}', [ProductEntryController::class, 'update']);


