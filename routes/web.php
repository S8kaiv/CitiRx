<?php

use App\Http\Controllers\BadgeController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiagnosticController;
use App\Http\Controllers\PracticeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReadinessController;
use App\Http\Controllers\RxVaultController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Diagnostic Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'student',
])
    ->prefix('diagnostic')
    ->name('diagnostic.')
    ->group(function () {

        Route::get(
            '/',
            [DiagnosticController::class, 'intro']
        )->name('intro');

        Route::post(
            '/start',
            [DiagnosticController::class, 'start']
        )->name('start');

        Route::get(
            '/{session}/take',
            [DiagnosticController::class, 'take']
        )->name('take');

        Route::post(
            '/{session}/answer',
            [DiagnosticController::class, 'saveAnswer']
        )->name('answer');

        Route::post(
            '/{session}/submit',
            [DiagnosticController::class, 'submit']
        )->name('submit');

        Route::get(
            '/{session}/results',
            [DiagnosticController::class, 'results']
        )->name('results');
    });

/*
|--------------------------------------------------------------------------
| Readiness Route
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'student',
])
    ->get(
        '/readiness',
        [ReadinessController::class, 'show']
    )
    ->name('readiness.show');

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Practice Route
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'student',
])
    ->prefix('practice')
    ->name('practice.')
    ->group(function () {
        Route::get(
            '/',
            [
                PracticeController::class,
                'intro',
            ]
        )->name('intro');

        Route::post(
            '/start',
            [
                PracticeController::class,
                'start',
            ]
        )->name('start');

        Route::get(
            '/{session}',
            [
                PracticeController::class,
                'show',
            ]
        )->name('show');

        Route::post(
            '/{session}/answer',
            [
                PracticeController::class,
                'answer',
            ]
        )->name('answer');

        Route::post(
            '/{session}/next',
            [
                PracticeController::class,
                'next',
            ]
        )->name('next');

        Route::get(
            '/{session}/summary',
            [
                PracticeController::class,
                'summary',
            ]
        )->name('summary');
    });

/*
|--------------------------------------------------------------------------
| Badges Route
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'student',
])
    ->get(
        '/badges',
        [
            BadgeController::class,
            'index',
        ]
    )
    ->name(
        'badges.index'
    );

/*
|--------------------------------------------------------------------------
| RxVault Route
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'student',
])
    ->get(
        '/vault',
        [
            RxVaultController::class,
            'index',
        ]
    )
    ->name(
        'vault.index'
    );

/*
|--------------------------------------------------------------------------
| Bookmarks Route
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'student',
])
    ->prefix('bookmarks')
    ->name('bookmarks.')
    ->group(function () {
        Route::get(
            '/',
            [BookmarkController::class, 'index']
        )->name('index');

        Route::put(
            '/questions/{question}',
            [BookmarkController::class, 'store']
        )->name('store');

        Route::delete(
            '/questions/{question}',
            [BookmarkController::class, 'destroyQuestion']
        )->name('destroyQuestion');

        Route::patch(
            '/{bookmark}/notes',
            [BookmarkController::class, 'updateNotes']
        )->name('updateNotes');

        Route::delete(
            '/{bookmark}',
            [BookmarkController::class, 'destroy']
        )->name('destroy');
    });
