<?php

use App\Http\Controllers\Planning\AnnualProcurementPlanController;
use App\Http\Controllers\Planning\MarketScopingController;
use App\Http\Controllers\Planning\PlanningWorkflowController;
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
        Route::patch('ppmps/{ppmp}/submit', [PlanningWorkflowController::class, 'submitPpmp'])
            ->name('ppmps.submit');
        Route::patch('ppmps/{ppmp}/review', [PlanningWorkflowController::class, 'startPpmpReview'])
            ->name('ppmps.review');
        Route::patch('ppmps/{ppmp}/approve', [PlanningWorkflowController::class, 'approvePpmp'])
            ->name('ppmps.approve');
        Route::patch('ppmps/{ppmp}/return', [PlanningWorkflowController::class, 'returnPpmp'])
            ->name('ppmps.return');

        Route::resource('annual-procurement-plans', AnnualProcurementPlanController::class)
            ->except(['show', 'destroy']);
        Route::patch('annual-procurement-plans/{annualProcurementPlan}/submit', [PlanningWorkflowController::class, 'submitAnnualProcurementPlan'])
            ->name('annual-procurement-plans.submit');
        Route::patch('annual-procurement-plans/{annualProcurementPlan}/recommend', [PlanningWorkflowController::class, 'recommendAnnualProcurementPlan'])
            ->name('annual-procurement-plans.recommend');
        Route::patch('annual-procurement-plans/{annualProcurementPlan}/approve', [PlanningWorkflowController::class, 'approveAnnualProcurementPlan'])
            ->name('annual-procurement-plans.approve');
        Route::patch('annual-procurement-plans/{annualProcurementPlan}/return', [PlanningWorkflowController::class, 'returnAnnualProcurementPlan'])
            ->name('annual-procurement-plans.return');
    });
});

require __DIR__.'/settings.php';
