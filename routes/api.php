<?php

use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

Route::post('/track', [TrackingController::class, 'track'])->name('api.track');
