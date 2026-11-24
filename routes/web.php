<?php

use App\Http\Controllers\FactController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SourceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [SiteController::class, 'index'])->name('dashboard');
    Route::get('/sites', [SiteController::class, 'index'])->name('sites.index');
    Route::get('/sites/create', [SiteController::class, 'create'])->name('sites.create');
    Route::post('/sites', [SiteController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('sites.store');

    Route::get('/sites/{site}', [SiteController::class, 'show'])
        ->middleware('can:view,site')
        ->name('sites.show');

    Route::get('/sites/{site}/sources', [SourceController::class, 'index'])
        ->middleware('can:view,site')
        ->name('sites.sources');

    Route::post('/sites/{site}/sources/{source}/retry', [SourceController::class, 'retry'])
        ->middleware(['can:update,site', 'throttle:20,1'])
        ->name('sites.sources.retry');

    Route::get('/sites/{site}/facts', [FactController::class, 'index'])
        ->middleware('can:view,site')
        ->name('sites.facts');

    Route::get('/sites/{site}/{tab}', [SiteController::class, 'tab'])
        ->whereIn('tab', ['sections', 'media', 'enquiries', 'settings'])
        ->middleware('can:view,site')
        ->name('sites.tab');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
