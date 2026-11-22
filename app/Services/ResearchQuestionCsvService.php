<?php

namespace App\Services;

use App\Models\Question;
use App\Models\ResearchFormVersion;
use App\Models\TosCompetency;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ResearchQuestionCsvService
{
    public const HEADERS = ['research_form', 'form_position', 'domain_number', 'competency_code', 'question_text', 'choice_a', 'choice_b', 'choice_c', 'choice_d', 'correct_choice', 'rationale', 'cognitive_level', 'source_reference', 'copyright_permission'];

    public function rows(string $path, ResearchFormVersion $version): array
    {
        $contents = file_get_contents($path);
        if ($contents === false || ! mb_check_encoding($contents, 'UTF-8')) {
            throw ValidationException::withMessages(['csv' => 'Save the CSV using UTF-8 encoding.']);
        }
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['csv' => 'The uploaded file cannot be read.']);
        }
        try {
            $headers = fgetcsv($handle, 0, ',', '"', '');
            $headers = array_map(fn ($h) => trim(preg_replace('/^\xEF\xBB\xBF/', '', $h)), $headers ?: []);
            if (array_diff(self::HEADERS, $headers) || count(array_unique($headers)) !== count($headers) || array_diff($headers, self::HEADERS)) {
                throw ValidationException::withMessages(['csv' => 'Use the research question template headers.']);
            }
            $competencies = TosCompetency::query()->with('domain')->get()->keyBy('competency_code');
            $existing = $version->questions()->get()->keyBy('form_position');
            $rows = [];
            $positions = [];
            $stems = [];
            $line = 1;
            while (($cells = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                $line++;
                if (count($cells) === 1 && blank($cells[0])) {
                    continue;
                }
                if (count($rows) >= 200) {
                    throw ValidationException::withMessages(['csv' => 'Upload no more than 200 rows at a time.']);
                }
                if (count($cells) !== count($headers)) {
                    $rows[] = ['row_number' => $line, 'form_position' => null, 'title' => 'Malformed CSV row', 'errors' => ['Column count does not match the header.'], 'payload' => null];

                    continue;
                }
                $row = array_map(fn ($v) => trim((string) $v), array_combine($headers, $cells));
                $validator = Validator::make($row, [
                    'research_form' => ['required', Rule::in([$version->research_form])],
                    'form_position' => ['required', 'integer', 'min:1', 'max:'.ResearchFormBlueprint::total($version->research_form)],
                    'domain_number' => ['required', 'integer', 'between:1,6'], 'competency_code' => ['required', 'string'],
                    'question_text' => ['required', 'string', 'max:10000'],
                    'choice_a' => ['required', 'string', 'max:5000'], 'choice_b' => ['required', 'string', 'max:5000'],
                    'choice_c' => ['required', 'string', 'max:5000'], 'choice_d' => ['required', 'string', 'max:5000'],
                    'correct_choice' => ['required', 'in:A,B,C,D'], 'rationale' => ['required', 'string', 'max:10000'],
                    'cognitive_level' => ['required', Rule::in(Question::COGNITIVE_LEVELS)],
                    'source_reference' => ['required', 'string', 'max:5000'],
                    'copyright_permission' => ['required', Rule::in(['permission confirmed', 'public domain', 'licensed'])],
                ]);
                $errors = $validator->errors()->all();
                if (str_contains(implode('', $row), "\0")) {
                    $errors[] = 'Null characters are not allowed.';
                }
                $competency = $competencies->get($row['competency_code']);
                if (! $competency || (int) $competency->domain?->domain_number !== (int) $row['domain_number']) {
                    $errors[] = 'The competency does not belong to the selected domain.';
                }
                $position = (int) $row['form_position'];
                if (isset($positions[$position])) {
                    $errors[] = 'This position is duplicated in the CSV.';
                }
                $positions[$position] = true;
                $stem = mb_strtolower(preg_replace('/\s+/u', ' ', $row['question_text']));
                if (isset($stems[$stem])) {
                    $errors[] = 'The question text is duplicated in this CSV.';
                }
                $stems[$stem] = true;
                if ($existing->get($position)?->contentIsFrozen()) {
                    $errors[] = 'The existing question at this position is locked.';
                }
                $choices = array_combine(['A', 'B', 'C', 'D'], [$row['choice_a'], $row['choice_b'], $row['choice_c'], $row['choice_d']]);
                if (count(array_unique(array_map(fn ($v) => mb_strtolower($v), $choices))) !== 4) {
                    $errors[] = 'The four choices must be different.';
                }
                $rows[] = [
                    'row_number' => $line, 'form_position' => $position, 'title' => $row['question_text'], 'errors' => $errors,
                    'payload' => ['form_position' => $position, 'competency_id' => $competency?->competency_id,
                        'question_text' => $row['question_text'], 'choices' => $choices, 'correct_choice' => $row['correct_choice'],
                        'hypercorrection_rationale' => $row['rationale'], 'cognitive_level' => $row['cognitive_level'],
                        'source_reference' => $row['source_reference'], 'copyright_permission' => $row['copyright_permission']],
                ];
            }
            if ($rows === []) {
                throw ValidationException::withMessages(['csv' => 'The CSV has no question rows.']);
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }
}
