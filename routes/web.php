<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CohortAssignmentImportController;
use App\Http\Controllers\Admin\CohortController;
use App\Http\Controllers\Admin\PilotControlController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\ResearchAnalyticsController;
use App\Http\Controllers\Admin\ResearchFormController;
use App\Http\Controllers\Admin\ResearchFormImportController;
use App\Http\Controllers\Admin\ResearchParticipantController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\Content\MicroPearlManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiagnosticController;
use App\Http\Controllers\FacultyDashboardController;
use App\Http\Controllers\FacultyInterventionController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MicroPearlController;
use App\Http\Controllers\MockBoardController;
use App\Http\Controllers\PracticeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\ReadinessController;
use App\Http\Controllers\RxVaultController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('welcome');

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
| Leaderboard Route
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'student',
])
    ->get(
        '/leaderboard',
        [
            LeaderboardController::class,
            'index',
        ],
    )
    ->name('leaderboard.index');

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
| Micro Pearls Library
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'pearl-editor',
])
    ->prefix('content/pearls')
    ->name('content.pearls.')
    ->group(function (): void {
        Route::get(
            '/',
            [
                MicroPearlManagementController::class,
                'index',
            ],
        )->name('index');

        Route::get(
            '/create',
            [
                MicroPearlManagementController::class,
                'create',
            ],
        )->name('create');

        Route::post(
            '/',
            [
                MicroPearlManagementController::class,
                'store',
            ],
        )->name('store');

        Route::get(
            '/{pearl:pearl_id}/edit',
            [
                MicroPearlManagementController::class,
                'edit',
            ],
        )
            ->whereUuid('pearl')
            ->name('edit');

        Route::put(
            '/{pearl:pearl_id}',
            [
                MicroPearlManagementController::class,
                'update',
            ],
        )
            ->whereUuid('pearl')
            ->name('update');

        Route::patch(
            '/{pearl:pearl_id}/submit-review',
            [
                MicroPearlManagementController::class,
                'submitReview',
            ],
        )
            ->whereUuid('pearl')
            ->name('submit-review');

        Route::patch(
            '/{pearl:pearl_id}/return-draft',
            [
                MicroPearlManagementController::class,
                'returnToDraft',
            ],
        )
            ->whereUuid('pearl')
            ->name('return-draft');

        Route::patch(
            '/{pearl:pearl_id}/publish',
            [
                MicroPearlManagementController::class,
                'publish',
            ],
        )
            ->whereUuid('pearl')
            ->name('publish');

        Route::patch(
            '/{pearl:pearl_id}/retire',
            [
                MicroPearlManagementController::class,
                'retire',
            ],
        )
            ->whereUuid('pearl')
            ->name('retire');
    });

Route::middleware([
    'auth',
    'verified',
])
    ->prefix('pearls')
    ->name('pearls.')
    ->group(function (): void {
        Route::get(
            '/',
            [
                MicroPearlController::class,
                'index',
            ]
        )->name('index');

        Route::get(
            '/{pearl}',
            [
                MicroPearlController::class,
                'show',
            ]
        )
            ->where(
                'pearl',
                '[A-Za-z0-9-]+'
            )
            ->name('show');
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
            '/research-forms',
            [
                ResearchFormController::class,
                'index',
            ],
        )->name(
            'research-forms.index',
        );

        Route::post(
            '/research-forms/versions/{researchFormVersion}/copy',
            [
                ResearchFormController::class,
                'copy',
            ],
        )
            ->whereUuid('researchFormVersion')
            ->name('research-forms.versions.copy');

        Route::patch(
            '/research-forms/versions/{researchFormVersion}/submit-review',
            [
                ResearchFormController::class,
                'submitReview',
            ],
        )
            ->whereUuid('researchFormVersion')
            ->name(
                'research-forms.versions.submit-review',
            );

        Route::patch(
            '/research-forms/versions/{researchFormVersion}/validate',
            [
                ResearchFormController::class,
                'markValidated',
            ],
        )
            ->whereUuid('researchFormVersion')
            ->name(
                'research-forms.versions.validate',
            );

        Route::patch(
            '/research-forms/versions/{researchFormVersion}/publish',
            [
                ResearchFormController::class,
                'publish',
            ],
        )
            ->whereUuid('researchFormVersion')
            ->name(
                'research-forms.versions.publish',
            );

        Route::get(
            '/research-forms/imports',
            [
                ResearchFormImportController::class,
                'index',
            ],
        )->name(
            'research-form-imports.index',
        );

        Route::post(
            '/research-forms/imports/preview',
            [
                ResearchFormImportController::class,
                'preview',
            ],
        )->name(
            'research-form-imports.preview',
        );

        Route::post(
            '/research-forms/imports/{researchFormImport}/commit',
            [
                ResearchFormImportController::class,
                'commit',
            ],
        )
            ->whereUuid('researchFormImport')
            ->name(
                'research-form-imports.commit',
            );

        Route::delete(
            '/research-forms/imports/{researchFormImport}',
            [
                ResearchFormImportController::class,
                'destroy',
            ],
        )
            ->whereUuid('researchFormImport')
            ->name(
                'research-form-imports.destroy',
            );

        Route::get(
            '/research-analytics',
            [
                ResearchAnalyticsController::class,
                'index',
            ],
        )->name(
            'research-analytics.index',
        );

        Route::get(
            '/pilot',
            [
                PilotControlController::class,
                'index',
            ],
        )->name('pilot.index');

        Route::patch(
            '/pilot/settings',
            [
                PilotControlController::class,
                'update',
            ],
        )->name('pilot.update');

        Route::get(
            '/system-health',
            [SystemHealthController::class, 'index'],
        )->name('system-health.index');

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

        /*
        |--------------------------------------------------------------------------
        | Administrator User Management
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/users/import',
            [
                CohortAssignmentImportController::class,
                'create',
            ],
        )->name('cohort-imports.create');

        Route::post(
            '/users/import/preview',
            [
                CohortAssignmentImportController::class,
                'preview',
            ],
        )->name('cohort-imports.preview');

        Route::get(
            '/users/import/{import}',
            [
                CohortAssignmentImportController::class,
                'show',
            ],
        )
            ->whereUuid('import')
            ->name('cohort-imports.show');

        Route::post(
            '/users/import/{import}/commit',
            [
                CohortAssignmentImportController::class,
                'commit',
            ],
        )
            ->whereUuid('import')
            ->name('cohort-imports.commit');

        Route::delete(
            '/users/import/{import}',
            [
                CohortAssignmentImportController::class,
                'destroy',
            ],
        )
            ->whereUuid('import')
            ->name('cohort-imports.destroy');

        Route::get(
            '/users',
            [UserController::class, 'index'],
        )->name('users.index');

        Route::get(
            '/users/{userId}/edit',
            [UserController::class, 'edit'],
        )
            ->whereUuid('userId')
            ->name('users.edit');

        Route::put(
            '/users/{userId}',
            [UserController::class, 'update'],
        )
            ->whereUuid('userId')
            ->name('users.update');

        Route::delete(
            '/users/{userId}',
            [UserController::class, 'destroy'],
        )
            ->whereUuid('userId')
            ->name('users.destroy');

        Route::patch(
            '/users/{userId}/restore',
            [UserController::class, 'restore'],
        )
            ->whereUuid('userId')
            ->name('users.restore');

        /*
        |--------------------------------------------------------------------------
        | Administrator Cohort Management
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'cohorts',
            CohortController::class,
        )->except([
            'destroy',
        ]);

        Route::patch(
            '/cohorts/{cohort}/post-test-window',
            [
                CohortController::class,
                'updatePostTestWindow',
            ],
        )->name('cohorts.post-test-window');

        Route::patch(
            '/cohorts/{cohort}/archive',
            [CohortController::class, 'archive'],
        )->name('cohorts.archive');

        Route::patch(
            '/cohorts/{cohort}/unarchive',
            [CohortController::class, 'unarchive'],
        )->name('cohorts.unarchive');

        /*
        |--------------------------------------------------------------------------
        | Administrator Audit History
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/audit-logs/export',
            [AuditLogController::class, 'export'],
        )->name('audit-logs.export');

        Route::get(
            '/audit-logs',
            [AuditLogController::class, 'index'],
        )->name('audit-logs.index');

        /*
        |--------------------------------------------------------------------------
        | Administrator Research Participants
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/research-participants',
            [
                ResearchParticipantController::class,
                'index',
            ],
        )->name('research-participants.index');

        Route::get(
            '/research-participants/{userId}/edit',
            [
                ResearchParticipantController::class,
                'edit',
            ],
        )
            ->whereUuid('userId')
            ->name('research-participants.edit');

        Route::patch(
            '/research-participants/{userId}',
            [
                ResearchParticipantController::class,
                'update',
            ],
        )
            ->whereUuid('userId')
            ->name('research-participants.update');
    });

require __DIR__.'/auth.php';
