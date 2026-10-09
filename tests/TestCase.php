<?php

namespace Tests;

use App\Models\Cohort;
use App\Models\User;
use App\Services\MockBoardService;
use App\Services\ResearchFormService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Shared Pest fixture properties.
 *
 * @property Cohort $cohort
 * @property User $student
 * @property MockBoardService $mockBoard
 * @property ResearchFormService $researchForms
 */
abstract class TestCase extends BaseTestCase
{
    //
}
