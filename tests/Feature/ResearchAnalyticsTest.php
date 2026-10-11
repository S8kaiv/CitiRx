<?php

use App\Models\User;
use App\Services\ResearchAnalyticsService;

beforeEach(function () {
    $this->withoutVite();

    config()->set('citirx.research.pre_test_form_version', 'pre_test_a_v1');
    config()->set('citirx.research.post_test_form_version', 'post_test_b_v1');
});

it('calculates the known signed-rank example', function () {
    $result = app(ResearchAnalyticsService::class)
        ->wilcoxonSignedRank([1, 2, -3, 4, 5]);

    expect($result['n'])->toBe(5)
        ->and($result['w_plus'])->toBe(12.0)
        ->and($result['w_minus'])->toBe(3.0)
        ->and($result['w_statistic'])->toBe(3.0)
        ->and($result['p_value'])->toBe(0.3125)
        ->and($result['method'])->toBe('exact two-sided signed-rank distribution');
});

it('assigns correct midranks when absolute differences are tied', function () {
    $result = app(ResearchAnalyticsService::class)
        ->wilcoxonSignedRank([1, 1, -1, 2, -2]);

    expect($result['w_plus'])->toBe(8.5)
        ->and($result['w_minus'])->toBe(6.5)
        ->and($result['w_statistic'])->toBe(6.5)
        ->and($result['method'])->toContain('tie correction');
});

it('applies continuity correction symmetrically', function () {
    $service = app(ResearchAnalyticsService::class);
    $positive = $service->wilcoxonSignedRank([1, 1, -1, 2, -2]);
    $negative = $service->wilcoxonSignedRank([-1, -1, 1, -2, 2]);

    expect($positive['z'])
        ->toBe(round(-$negative['z'], 6))
        ->and($positive['p_value'])
        ->toBe($negative['p_value']);
});

it('handles zero differences and empty readiness data', function () {
    $service = app(ResearchAnalyticsService::class);
    $wilcoxon = $service->wilcoxonSignedRank([0, 0, 0]);
    $readiness = $service->readinessAccuracy();

    expect($wilcoxon['n'])->toBe(0)
        ->and($wilcoxon['p_value'])->toBe(1.0)
        ->and($readiness['n'])->toBe(0)
        ->and($readiness['mae'])->toBeNull()
        ->and($readiness['rmse'])->toBeNull();
});

it('calculates descriptive statistics with sample standard deviation', function () {
    $result = app(ResearchAnalyticsService::class)
        ->descriptives([10, 20, 30, 40, 50]);

    expect($result['n'])->toBe(5)
        ->and($result['mean'])->toBe(30.0)
        ->and($result['median'])->toBe(30.0)
        ->and($result['sd'])->toBe(15.81)
        ->and($result['min'])->toBe(10.0)
        ->and($result['max'])->toBe(50.0);
});

it('allows only administrators to view research analytics', function () {
    $student = User::factory()->create(['role' => 'student']);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->get(route('admin.research-analytics.index'))
        ->assertRedirect(route('login'));

    $this->actingAs($student)
        ->get(route('admin.research-analytics.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.research-analytics.index'))
        ->assertOk()
        ->assertSee('Research Analytics');
});

it('blocks faculty from the administrator analytics report', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $this->actingAs($faculty)
        ->get(route('admin.research-analytics.index'))
        ->assertForbidden();
});

it('returns no sample standard deviation for one observation', function () {
    $result = app(ResearchAnalyticsService::class)
        ->descriptives([50]);

    expect($result['n'])->toBe(1)
        ->and($result['mean'])->toBe(50.0)
        ->and($result['sd'])->toBeNull();
});
