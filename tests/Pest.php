<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

require_once __DIR__.'/Support/ResearchFormFixtures.php';

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Browser');

expect()->extend(
    'toBeOne',
    function () {
        return $this->toBe(1);
    },
);
