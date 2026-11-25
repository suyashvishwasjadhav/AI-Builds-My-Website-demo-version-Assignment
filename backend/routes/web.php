<?php

use App\Http\Controllers\DomainController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FactController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\PublishController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SocialCardController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\TemplateDemoController;
use App\Http\Controllers\TranslationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/api/landing-data', [LandingController::class, 'data'])->name('api.landing.data');
Route::get('/templates/{template}', [TemplateDemoController::class, 'show'])
    ->whereIn('template', ['clean', 'bold', 'warm'])
    ->name('templates.demo');

Route::get('/s/{slug}', [PublicSiteController::class, 'show'])->name('public.site');
Route::get('/s/{slug}/og-image.svg', [SocialCardController::class, 'ogImage'])->name('public.site.og');
Route::get('/s/{slug}/favicon.svg', [SocialCardController::class, 'favicon'])->name('public.site.favicon');
Route::get('/s/{slug}/sitemap.xml', [SeoController::class, 'sitemap'])->name('public.site.sitemap');
Route::get('/s/{slug}/robots.txt', [SeoController::class, 'robots'])->name('public.site.robots');
Route::post('/s/{slug}/enquiries', [EnquiryController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('public.enquiries.store');

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

    Route::get('/sites/{site}/studio', [PreviewController::class, 'studio'])
        ->middleware('can:view,site')
        ->name('sites.studio');

    Route::patch('/sites/{site}/template', [PreviewController::class, 'updateTemplate'])
        ->middleware('can:update,site')
        ->name('sites.template.update');

    Route::patch('/sites/{site}/theme-config', [PreviewController::class, 'updateThemeConfig'])
        ->middleware('can:update,site')
        ->name('sites.theme_config.update');

    Route::get('/sites/{site}/sections', [SectionController::class, 'index'])
        ->middleware('can:view,site')
        ->name('sites.sections');

    Route::patch('/sites/{site}/sections/{section}', [SectionController::class, 'update'])
        ->middleware('can:update,site')
        ->name('sites.sections.update');

    Route::patch('/sites/{site}/sections/{section}/inline', [SectionController::class, 'inlineUpdate'])
        ->middleware('can:update,site')
        ->name('sites.sections.inline');

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

    Route::patch('/sites/{site}/whatsapp', [SiteController::class, 'updateWhatsapp'])
        ->middleware('can:update,site')
        ->name('sites.whatsapp.update');

    Route::post('/sites/{site}/translate', [TranslationController::class, 'store'])
        ->middleware(['can:update,site', 'throttle:10,1'])
        ->name('sites.translate');

    Route::get('/sites/{site}/qr/download', [QrCodeController::class, 'download'])
        ->middleware('can:view,site')
        ->name('sites.qr.download');

    Route::get('/sites/{site}/qr/poster', [QrCodeController::class, 'poster'])
        ->middleware('can:view,site')
        ->name('sites.qr.poster');

    Route::get('/sites/{site}/export/zip', [ExportController::class, 'zip'])
        ->middleware(['can:view,site', 'throttle:10,1'])
        ->name('sites.export.zip');

    Route::get('/sites/{site}/enquiries', [EnquiryController::class, 'index'])
        ->middleware('can:view,site')
        ->name('sites.enquiries');

    Route::get('/sites/{site}/enquiries/export', [EnquiryController::class, 'exportCsv'])
        ->middleware('can:view,site')
        ->name('sites.enquiries.export');

    Route::patch('/sites/{site}/enquiries/{enquiry}/status', [EnquiryController::class, 'updateStatus'])
        ->middleware('can:update,site')
        ->name('sites.enquiries.status');

    Route::post('/sites/{site}/enquiries/{enquiry}/read', [EnquiryController::class, 'markRead'])
        ->middleware('can:update,site')
        ->name('sites.enquiries.read');

    Route::patch('/sites/{site}/domain', [DomainController::class, 'update'])
        ->middleware('can:update,site')
        ->name('sites.domain.update');

    Route::post('/sites/{site}/domain/verify', [DomainController::class, 'verify'])
        ->middleware(['can:update,site', 'throttle:10,1'])
        ->name('sites.domain.verify');

    Route::get('/sites/{site}/media', [MediaController::class, 'index'])
        ->middleware('can:view,site')
        ->name('sites.media');

    Route::post('/sites/{site}/media', [MediaController::class, 'store'])
        ->middleware(['can:update,site', 'throttle:20,1'])
        ->name('sites.media.store');

    Route::patch('/sites/{site}/media/{media}/hero', [MediaController::class, 'setHero'])
        ->middleware('can:update,site')
        ->name('sites.media.hero');

    Route::delete('/sites/{site}/media/{media}', [MediaController::class, 'destroy'])
        ->middleware('can:update,site')
        ->name('sites.media.destroy');

    Route::get('/sites/{site}/{tab}', [SiteController::class, 'tab'])
        ->whereIn('tab', ['settings'])
        ->middleware('can:view,site')
        ->name('sites.tab');

    Route::patch('/sites/{site}/settings', [SiteController::class, 'updateSettings'])
        ->middleware('can:update,site')
        ->name('sites.settings.update');

    Route::delete('/sites/{site}', [SiteController::class, 'destroy'])
        ->middleware('can:delete,site')
        ->name('sites.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
