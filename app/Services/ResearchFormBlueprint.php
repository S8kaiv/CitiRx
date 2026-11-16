<?php

namespace App\Services;

use App\Models\Question;
use InvalidArgumentException;

final class ResearchFormBlueprint
{
    public const QUOTAS = [
        Question::FORM_PRE_TEST_A => [
            1 => ['PC-01' => 4, 'PC-02' => 5, 'PC-03' => 1],
            2 => ['PB-01' => 5, 'PB-02' => 5],
            3 => ['PP-01' => 2, 'PP-02' => 4, 'PP-03' => 2, 'PP-04' => 2],
            4 => ['PK-01' => 6, 'PK-02' => 2, 'PK-03' => 2],
            5 => ['PH-01' => 3, 'PH-02' => 3, 'PH-03' => 2, 'PH-04' => 2],
            6 => ['QC-01' => 3, 'QC-02' => 2, 'QC-03' => 3, 'QC-04' => 2],
        ],
        Question::FORM_POST_TEST_B => [
            1 => ['PC-01' => 9, 'PC-02' => 9, 'PC-03' => 2],
            2 => ['PB-01' => 8, 'PB-02' => 7],
            3 => ['PP-01' => 4, 'PP-02' => 6, 'PP-03' => 4, 'PP-04' => 4],
            4 => ['PK-01' => 9, 'PK-02' => 3, 'PK-03' => 3],
            5 => ['PH-01' => 5, 'PH-02' => 5, 'PH-03' => 4, 'PH-04' => 3],
            6 => ['QC-01' => 4, 'QC-02' => 4, 'QC-03' => 4, 'QC-04' => 3],
        ],
    ];

    public static function quotas(string $form): array
    {
        return self::QUOTAS[$form]
            ?? throw new InvalidArgumentException("Unknown research form: {$form}");
    }

    public static function total(string $form): int
    {
        return array_sum(array_map('array_sum', self::quotas($form)));
    }

    public static function domainTotal(string $form, int $domain): int
    {
        return array_sum(self::quotas($form)[$domain] ?? []);
    }
}
