<?php

use Illuminate\Support\Facades\Route;


Route::middleware('rate_limit')->group(function () {
    Route::get('/health', function () {
        return response()->json(['status' => 'ok']);
    });
});
