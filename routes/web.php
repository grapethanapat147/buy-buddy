<?php

use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\WizardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WizardController::class, 'show'])->name('wizard');
Route::post('/wizard', [WizardController::class, 'store'])->name('wizard.store');
Route::get('/recommendations', [RecommendationController::class, 'index'])->name('recommendations');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::post('/plan/items/{product}', [PlanController::class, 'add'])->name('plan.add');
Route::delete('/plan/items/{product}', [PlanController::class, 'remove'])->name('plan.remove');
Route::get('/plan', [PlanController::class, 'show'])->name('plan.show');
