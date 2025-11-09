<?php

use Illuminate\Support\Facades\Route;

// Serve the Vue SPA for all frontend routes
Route::view('/{any}', 'app')->where('any', '.*');