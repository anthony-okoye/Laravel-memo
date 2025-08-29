<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductEntryController;

Route::get('/', function () {
    return app(ProductEntryController::class)->index(request());
});
