<?php

use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\ResearchAnalyticsController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiagnosticController;
use App\Http\Controllers\FacultyDashboardController;
use App\Http\Controllers\FacultyInterventionController;
use App\Http\Controllers\MockBoardController;
use App\Http\Controllers\PracticeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\ReadinessController;
use App\Http\Controllers\RxVaultController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(
        Auth::check() ? 'dashboard' : 'login'
    );
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
    ->name('badges.index');

/*
|--------------------------------------------------------------------------
| RxVault Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'student',
])
    ->prefix('vault')
    ->name('vault.')
    ->group(function () {
        Route::get(
            '/',
            [
                RxVaultController::class,
                'index',
            ]
        )->name('index');

        Route::post(
            '/drill/{mode}',
            [
                RxVaultController::class,
                'startDrill',
            ]
        )
            ->where(
                'mode',
                'mistakes|bookmarks'
            )
            ->name('drill');
    });

/*
|--------------------------------------------------------------------------
| Bookmarks Actions (Storage & Updates)
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

/*
|--------------------------------------------------------------------------
| Progress Route
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'student',
])
    ->get(
        '/progress',
        [
            ProgressController::class,
            'index',
        ]
    )
    ->name('progress.index');

/*
|--------------------------------------------------------------------------
| Mock Board Route
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'student',
])
    ->prefix('mock-board')
    ->name('mock-board.')
    ->group(function () {
        Route::get(
            '/',
            [MockBoardController::class, 'intro'],
        )->name('intro');

        Route::post(
            '/start',
            [MockBoardController::class, 'start'],
        )->name('start');

        Route::get(
            '/{session}',
            [MockBoardController::class, 'take'],
        )->name('take');

        Route::patch(
            '/{session}/questions/{question}/answer',
            [MockBoardController::class, 'answer'],
        )->name('answer');

        Route::post(
            '/{session}/submit',
            [MockBoardController::class, 'submit'],
        )->name('submit');

        Route::get(
            '/{session}/results',
            [MockBoardController::class, 'results'],
        )->name('results');
    });

/*
|--------------------------------------------------------------------------
| Faculty Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'faculty',
])
    ->prefix('faculty')
    ->name('faculty.')
    ->group(function (): void {
        Route::get(
            '/',
            [
                FacultyDashboardController::class,
                'index',
            ],
        )->name(
            'dashboard',
        );

        Route::get(
            '/interventions',
            [
                FacultyInterventionController::class,
                'index',
            ],
        )->name(
            'interventions.index',
        );

        Route::post(
            '/interventions',
            [
                FacultyInterventionController::class,
                'store',
            ],
        )->name(
            'interventions.store',
        );

        Route::patch(
            '/interventions/{intervention}/complete',
            [
                FacultyInterventionController::class,
                'complete',
            ],
        )->whereUuid(
            'intervention',
        )->name(
            'interventions.complete',
        );
    });

/*
|--------------------------------------------------------------------------
| Administrator Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'admin',
])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get(
            '/research-analytics',
            [
                ResearchAnalyticsController::class,
                'index',
            ],
        )->name(
            'research-analytics.index',
        );

        /*
        |--------------------------------------------------------------------------
        | Administrator Question Bank
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/questions',
            [
                QuestionController::class,
                'index',
            ],
        )->name(
            'questions.index',
        );

        Route::get(
            '/questions/{question}',
            [
                QuestionController::class,
                'show',
            ],
        )->name(
            'questions.show',
        );

        Route::get(
            '/questions/{question}/edit',
            [
                QuestionController::class,
                'edit',
            ],
        )->name(
            'questions.edit',
        );

        Route::put(
            '/questions/{question}',
            [
                QuestionController::class,
                'update',
            ],
        )->name(
            'questions.update',
        );

        Route::patch(
            '/questions/{question}/status',
            [
                QuestionController::class,
                'updateStatus',
            ],
        )->name(
            'questions.status',
        );
    });

require __DIR__.'/auth.php';
