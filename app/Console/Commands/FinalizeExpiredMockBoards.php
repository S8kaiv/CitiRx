<?php

namespace App\Console\Commands;

use App\Models\AssessmentSession;
use App\Services\MockBoardService;
use Illuminate\Console\Command;
use Throwable;

class FinalizeExpiredMockBoards extends Command
{
    /**
     * The console command name.
     *
     * Run manually with:
     * php artisan mock-board:finalize-expired
     */
    protected $signature =
        'mock-board:finalize-expired';

    /**
     * The console command description.
     */
    protected $description =
        'Finalize expired, incomplete research post-test sessions';

    /**
     * Execute the console command.
     */
    public function handle(
        MockBoardService $mockBoard,
    ): int {
        $finalized = 0;
        $failed = 0;

        /*
         * Load only sessions that:
         *
         * - are official Mock Board post-tests;
         * - are not completed;
         * - have a server deadline;
         * - have already reached that deadline.
         *
         * Only session_id is selected because MockBoardService
         * reloads and locks the complete session before scoring.
         */
        AssessmentSession::query()
            ->where(
                'session_type',
                'mock_board',
            )
            ->where(
                'research_phase',
                'post_test',
            )
            ->whereNull('completed_at')
            ->whereNotNull('expires_at')
            ->where(
                'expires_at',
                '<=',
                now(),
            )
            ->select('session_id')
            ->lazyById(
                100,
                column: 'session_id',
            )
            ->each(function (
                AssessmentSession $session,
            ) use (
                $mockBoard,
                &$finalized,
                &$failed,
            ): void {
                try {
                    /*
                     * MockBoardService reloads and locks the
                     * session, scores persisted responses,
                     * and determines the final result.
                     */
                    $mockBoard->submit(
                        $session,
                        'expired',
                    );

                    $finalized++;
                } catch (Throwable $exception) {
                    /*
                     * Do not allow one corrupt session to prevent
                     * other expired sessions from finalizing.
                     */
                    $failed++;

                    report($exception);

                    $this->error(
                        'Failed to finalize session '
                            ."{$session->session_id}.",
                    );
                }
            });

        $this->info(
            "Finalized: {$finalized}; "
                ."failed: {$failed}.",
        );

        return $failed === 0
            ? self::SUCCESS
            : self::FAILURE;
    }
}
