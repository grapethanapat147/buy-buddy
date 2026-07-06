<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/__ping', fn () => Inertia::render('Ping', ['ok' => true]));
