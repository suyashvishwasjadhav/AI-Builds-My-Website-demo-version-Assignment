<?php

use App\Http\Controllers\FactController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\PublishController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SourceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/s/{slug}', [PublicSiteController::class, 'show'])->name('public.site');

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

    Route::post('/sites/{site}/questions/{question}/answer', [QuestionController::class, 'answer'])
        ->middleware('can:update,site')
        ->name('sites.questions.answer');

    Route::post('/sites/{site}/questions/{question}/skip', [QuestionController::class, 'skip'])
        ->middleware('can:update,site')
        ->name('sites.questions.skip');

    Route::post('/sites/{site}/generate', [GenerationController::class, 'generate'])
        ->middleware(['can:update,site', 'throttle:10,1'])
        ->name('sites.generate');

    Route::post('/sites/{site}/generate-fallback', [GenerationController::class, 'fallback'])
        ->middleware('can:update,site')
        ->name('sites.generate.fallback');

    Route::get('/sites/{site}/generation-status', [GenerationController::class, 'status'])
        ->middleware('can:view,site')
        ->name('sites.generate.status');

    Route::get('/sites/{site}/preview', [PreviewController::class, 'show'])
        ->middleware('can:view,site')
        ->name('sites.preview');

    Route::patch('/sites/{site}/template', [PreviewController::class, 'updateTemplate'])
        ->middleware('can:update,site')
        ->name('sites.template.update');

    Route::get('/sites/{site}/sections', [SectionController::class, 'index'])
        ->middleware('can:view,site')
        ->name('sites.sections');

    Route::patch('/sites/{site}/sections/{section}', [SectionController::class, 'update'])
        ->middleware('can:update,site')
        ->name('sites.sections.update');

    Route::post('/sites/{site}/sections/{section}/regenerate', [SectionController::class, 'regenerate'])
        ->middleware(['can:update,site', 'throttle:10,1'])
        ->name('sites.sections.regenerate');

    Route::post('/sites/{site}/sections/{section}/toggle', [SectionController::class, 'toggle'])
        ->middleware('can:update,site')
        ->name('sites.sections.toggle');

    Route::post('/sites/{site}/sections/{section}/versions/{version}/restore', [SectionController::class, 'restore'])
        ->middleware('can:update,site')
        ->name('sites.sections.restore');

    Route::post('/sites/{site}/publish', [PublishController::class, 'publish'])
        ->middleware('can:update,site')
        ->name('sites.publish');

    Route::post('/sites/{site}/unpublish', [PublishController::class, 'unpublish'])
        ->middleware('can:update,site')
        ->name('sites.unpublish');

    Route::patch('/sites/{site}/slug', [PublishController::class, 'updateSlug'])
        ->middleware('can:update,site')
        ->name('sites.slug.update');

    Route::get('/sites/{site}/{tab}', [SiteController::class, 'tab'])
        ->whereIn('tab', ['media', 'enquiries', 'settings'])
        ->middleware('can:view,site')
        ->name('sites.tab');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
