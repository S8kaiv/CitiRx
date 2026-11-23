<?php

namespace App\Services;

use App\Http\Requests\CommitPracticeQuestionImportRequest;
use App\Http\Requests\StorePracticeQuestionRequest;
use App\Models\PracticeQuestionImport;
use App\Models\TosCompetency;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PracticeQuestionImportService
{
    public const HEADERS = ['competency_code', 'question_text', 'choice_a', 'choice_b', 'choice_c', 'choice_d', 'correct_choice', 'rationale', 'cognitive_level', 'source_reference', 'copyright_permission'];

    public function preview(User $uploader, UploadedFile $file): PracticeQuestionImport
    {
        abort_unless(in_array($uploader->role, ['admin', 'dean'], true), 403);
        $contents = $file->get();
        if (strlen($contents) > 2 * 1024 * 1024 || ! mb_check_encoding($contents, 'UTF-8') || str_contains($contents, "\0")) {
            throw ValidationException::withMessages(['csv' => 'Use a UTF-8 CSV up to 2 MB, without null characters.']);
        }

        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['csv' => 'The CSV cannot be read. Upload it again.']);
        }
        try {
            $headers = fgetcsv($handle, 0, ',', '"', '');
            $headers = array_map(fn ($value) => trim((string) $value), $headers ?: []);
            if ($headers !== []) {
                $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            }
            if (count($headers) !== count(self::HEADERS) || array_diff(self::HEADERS, $headers) || count(array_unique($headers)) !== count($headers)) {
                throw ValidationException::withMessages(['csv' => 'Use the practice question template headers and save as CSV UTF-8 (comma-delimited).']);
            }

            $competencies = TosCompetency::query()->pluck('competency_id', 'competency_code');
            $rows = [];
            $stems = [];
            $line = 1;
            while (($cells = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                $line++;
                if (count($cells) === 1 && blank($cells[0])) {
                    continue;
                }
                if (count($rows) >= 200) {
                    throw ValidationException::withMessages(['csv' => 'Import no more than 200 questions per file.']);
                }
                if (count($cells) !== count($headers)) {
                    $rows[] = ['row_number' => $line, 'competency_code' => '', 'title' => 'Malformed CSV row', 'errors' => ['Column count does not match the header. Quote text containing commas or line breaks.'], 'payload' => null];

                    continue;
                }
                $row = array_map(fn ($value) => trim((string) $value), array_combine($headers, $cells));
                $payload = [
                    'competency_id' => $competencies->get($row['competency_code']),
                    'question_text' => $row['question_text'],
                    'choices' => [],
                    'correct_choice' => strtoupper($row['correct_choice']),
                    'hypercorrection_rationale' => $row['rationale'],
                    'cognitive_level' => $row['cognitive_level'] !== '' ? $row['cognitive_level'] : null,
                    'source_reference' => $row['source_reference'],
                    'copyright_permission' => $row['copyright_permission'],
                ];
                foreach (['A', 'B', 'C', 'D'] as $letter) {
                    $payload['choices'][$letter] = ['text' => $row['choice_'.strtolower($letter)]];
                }
                $errors = $this->validationErrors($payload);
                if ($payload['competency_id'] === null) {
                    $errors[] = 'Unknown competency code. Use a code from the competency reference.';
                }
                $stem = $this->normalized($row['question_text']);
                if (isset($stems[$stem])) {
                    $errors[] = 'This question is repeated in the CSV.';
                }
                $stems[$stem] = true;
                $rows[] = ['row_number' => $line, 'competency_code' => $row['competency_code'], 'title' => $row['question_text'], 'errors' => $errors, 'payload' => $payload];
            }
            if ($rows === []) {
                throw ValidationException::withMessages(['csv' => 'The CSV contains no question rows. Add one question per row beneath the headers.']);
            }

            // Keep the bounded preview in the database, not on the web server's temporary disk.
            return PracticeQuestionImport::query()->create([
                'uploader_id' => $uploader->user_id,
                'original_filename' => mb_substr($file->getClientOriginalName(), 0, 255),
                'preview_rows' => $rows,
                'row_count' => count($rows),
                'expires_at' => now()->addMinutes(30),
            ]);
        } finally {
            fclose($handle);
        }
    }

    public function commit(User $uploader, PracticeQuestionImport $import, array $selectedRows): int
    {
        abort_unless(in_array($uploader->role, ['admin', 'dean'], true), 403);

        return DB::transaction(function () use ($uploader, $import, $selectedRows): int {
            $import = PracticeQuestionImport::query()->whereKey($import->import_id)->lockForUpdate()->firstOrFail();
            abort_unless($import->uploader_id === $uploader->user_id, 403);
            if (! $import->canCommit()) {
                throw ValidationException::withMessages(['csv' => 'This preview was imported or expired. Upload a new CSV to continue.']);
            }
            $selection = Validator::make(['selected_rows' => $selectedRows], (new CommitPracticeQuestionImportRequest)->rules())->validate();
            $rows = collect($import->preview_rows)->keyBy('row_number');
            $questions = app(QuestionEditService::class);
            foreach ($selection['selected_rows'] as $number) {
                $row = $rows->get((int) $number);
                if (! $row || $row['errors'] !== [] || ! is_array($row['payload'])) {
                    throw ValidationException::withMessages(['selected_rows' => 'Select only eligible rows from this preview. No questions were imported.']);
                }
                if ($this->validationErrors($row['payload']) !== []) {
                    throw ValidationException::withMessages(['selected_rows' => 'Row '.$number.' is no longer valid. Upload a fresh CSV. No questions were imported.']);
                }
                try {
                    $questions->createPractice($uploader, $row['payload']);
                } catch (InvalidArgumentException $exception) {
                    throw ValidationException::withMessages(['selected_rows' => 'Row '.$number.': '.$exception->getMessage().' No questions were imported.']);
                }
            }
            $import->forceFill(['committed_at' => now(), 'preview_rows' => null])->save();

            return count($selection['selected_rows']);
        });
    }

    private function validationErrors(array $payload): array
    {
        $request = new StorePracticeQuestionRequest;
        $errors = Validator::make($payload, $request->rules(), [], $request->attributes())->errors()->all();
        $choices = array_map(fn ($choice) => $this->normalized($choice['text']), $payload['choices']);
        if (collect($choices)->unique()->count() !== 4) {
            $errors[] = 'Every answer choice must have different text.';
        }

        return $errors;
    }

    private function normalized(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));
    }
}
