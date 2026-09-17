<?php

use App\Http\Controllers\Planning\MarketScopingController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::prefix('planning')->name('planning.')->group(function () {
        Route::resource('market-scopings', MarketScopingController::class)
            ->except(['show', 'destroy']);
    });
});

require __DIR__.'/settings.php';
