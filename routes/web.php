<?php

use App\Http\Controllers\Planning\AnnualProcurementPlanController;
use App\Http\Controllers\Planning\MarketScopingController;
use App\Http\Controllers\Planning\PpmpController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::prefix('planning')->name('planning.')->group(function () {
        Route::resource('market-scopings', MarketScopingController::class)
            ->except(['show', 'destroy']);
        Route::resource('ppmps', PpmpController::class)
            ->except(['show', 'destroy']);
        Route::resource('annual-procurement-plans', AnnualProcurementPlanController::class)
            ->except(['show', 'destroy']);
    });
});

require __DIR__.'/settings.php';
