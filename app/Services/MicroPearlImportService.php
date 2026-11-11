<?php

namespace App\Services;

use App\Models\MicroPearl;
use App\Models\MicroPearlImport;
use App\Models\TosCompetency;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;
use Throwable;

class MicroPearlImportService
{
    public const MAX_ROWS = 200;

    public const HEADERS = [
        'competency_code',
        'title',
        'mnemonic_summary',
        'lesson_content',
        'learning_objectives',
        'key_points',
        'common_pitfalls',
        'recall_prompts',
        'source_references',
        'estimated_read_seconds',
        'order_index',
        'rights_status',
        'is_ai_generated',
        'ai_enrichment_allowed',
        'generation_provider',
        'generation_model',
        'prompt_version',
    ];

    public function __construct(
        protected MicroPearlWorkflowService $workflow,
    ) {}

    public function createPreview(
        User $uploader,
        UploadedFile $file,
    ): MicroPearlImport {
        $this->assertEditor($uploader);

        $storedPath = $this->storeUpload(
            $uploader,
            $file,
        );

        try {
            $absolutePath = Storage::disk('local')
                ->path($storedPath);

            $rows = $this->parseAndValidate(
                $absolutePath,
            );

            $hash = hash_file(
                'sha256',
                $absolutePath,
            );

            if ($hash === false) {
                throw new RuntimeException(
                    'The uploaded CSV could not be hashed.',
                );
            }

            $minutes = max(
                5,
                min(
                    120,
                    (int) config(
                        'citirx.content.'
                            .'pearl_import_preview_minutes',
                        30,
                    ),
                ),
            );

            return MicroPearlImport::query()->create([
                'uploader_id' => $uploader->user_id,

                'status' => MicroPearlImport::STATUS_PREVIEWED,

                'original_filename' => $this
                    ->safeOriginalFilename($file),

                'storage_path' => $storedPath,

                'file_sha256' => $hash,

                'file_size' => Storage::disk('local')
                    ->size($storedPath),

                'row_count' => count($rows),

                'preview_rows' => array_map(
                    fn (array $row): array => $row['preview'],
                    $rows,
                ),

                'lock_version' => 1,

                'expires_at' => now()
                    ->addMinutes($minutes),
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete(
                $storedPath,
            );

            throw $exception;
        }
    }

    public function commit(
        User $uploader,
        MicroPearlImport $import,
        int $expectedLockVersion,
        string $reason,
    ): MicroPearlImport {
        $this->assertEditor($uploader);

        $ownedPreviewConfirmed = false;

        try {
            $committed = DB::transaction(
                function () use (
                    $uploader,
                    $import,
                    $expectedLockVersion,
                    $reason,
                    &$ownedPreviewConfirmed,
                ): MicroPearlImport {
                    $import = MicroPearlImport::query()
                        ->whereKey($import->import_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (
                        $import->uploader_id
                        !== $uploader->user_id
                    ) {
                        throw new LogicException(
                            'This preview belongs to another user.',
                        );
                    }

                    if (
                        $import->status
                        !== MicroPearlImport::STATUS_PREVIEWED
                    ) {
                        throw new LogicException(
                            'This import preview can no longer be committed.',
                        );
                    }

                    if (
                        (int) $import->lock_version
                        !== $expectedLockVersion
                    ) {
                        throw new LogicException(
                            'This preview changed in another browser session. Refresh the page.',
                        );
                    }

                    $ownedPreviewConfirmed = true;

                    if ($import->isExpired()) {
                        throw new LogicException(
                            'This preview expired. Upload the CSV again.',
                        );
                    }

                    $absolutePath = $this
                        ->assertStoredFileIntact(
                            $import,
                        );

                    /*
                     * Reparse and revalidate at commit time.
                     * The preview is never trusted as the source
                     * of the actual database changes.
                     */
                    $rows = $this->parseAndValidate(
                        $absolutePath,
                    );

                    if (
                        count($rows)
                        !== (int) $import->row_count
                    ) {
                        throw new LogicException(
                            'The CSV row count changed after preview. Upload it again.',
                        );
                    }

                    foreach ($rows as $row) {
                        /*
                         * This creates a private draft and records
                         * the existing immutable MicroPearlEdit.
                         */
                        $this->workflow->create(
                            $uploader,
                            $row['payload'],
                        );
                    }

                    $import->forceFill([
                        'status' => MicroPearlImport::STATUS_COMMITTED,

                        'reason' => trim($reason),

                        'committed_at' => now(),

                        'failure_message' => null,

                        'lock_version' => (
                            (int) $import->lock_version
                        ) + 1,
                    ])->save();

                    return $import;
                },
            );
        } catch (Throwable $exception) {
            if ($ownedPreviewConfirmed) {
                $this->markFailed($import);
            }

            throw $exception;
        }

        $this->purgeStoredFile($committed);

        return $committed->fresh('uploader');
    }

    public function cancel(
        User $uploader,
        MicroPearlImport $import,
    ): void {
        $this->assertEditor($uploader);

        $cancelled = DB::transaction(
            function () use (
                $uploader,
                $import,
            ): MicroPearlImport {
                $import = MicroPearlImport::query()
                    ->whereKey($import->import_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $import->uploader_id
                    !== $uploader->user_id
                ) {
                    throw new LogicException(
                        'This preview belongs to another user.',
                    );
                }

                if (
                    ! in_array(
                        $import->status,
                        [
                            MicroPearlImport::STATUS_PREVIEWED,
                            MicroPearlImport::STATUS_FAILED,
                        ],
                        true,
                    )
                ) {
                    throw new LogicException(
                        'This import preview can no longer be cancelled.',
                    );
                }

                $import->forceFill([
                    'status' => MicroPearlImport::STATUS_CANCELLED,

                    'lock_version' => (
                        (int) $import->lock_version
                    ) + 1,
                ])->save();

                return $import;
            },
        );

        $this->purgeStoredFile($cancelled);
    }

    public function pruneExpired(): int
    {
        $ids = MicroPearlImport::query()
            ->where(
                'expires_at',
                '<=',
                now(),
            )
            ->whereNotNull('storage_path')
            ->pluck('import_id');

        $processed = 0;

        foreach ($ids as $id) {
            $import = DB::transaction(
                function () use (
                    $id,
                ): ?MicroPearlImport {
                    $import = MicroPearlImport::query()
                        ->whereKey($id)
                        ->lockForUpdate()
                        ->first();

                    if (
                        ! $import
                        || $import->expires_at->isFuture()
                    ) {
                        return null;
                    }

                    if (
                        $import->status
                        === MicroPearlImport::STATUS_PREVIEWED
                    ) {
                        $import->forceFill([
                            'status' => MicroPearlImport::STATUS_EXPIRED,

                            'lock_version' => (
                                (int) $import->lock_version
                            ) + 1,
                        ])->save();
                    }

                    return $import;
                },
            );

            if (! $import) {
                continue;
            }

            $this->purgeStoredFile($import);

            $processed++;
        }

        return $processed;
    }

    /**
     * @return array<int, array{
     *     preview: array<string, mixed>,
     *     payload: array<string, mixed>
     * }>
     */
    private function parseAndValidate(
        string $absolutePath,
    ): array {
        $records = $this->readCsv(
            $absolutePath,
        );

        $competencyCodes = collect($records)
            ->map(
                fn (array $record): string => mb_strtoupper(
                    trim(
                        (string) $record['values']['competency_code'],
                    ),
                ),
            )
            ->filter()
            ->unique()
            ->values();

        $competencies = TosCompetency::query()
            ->whereIn(
                'competency_code',
                $competencyCodes,
            )
            ->get()
            ->keyBy('competency_code');

        $competencyIds = $competencies
            ->pluck('competency_id')
            ->values();

        $existingPearls = $competencyIds->isEmpty()
            ? collect()
            : MicroPearl::query()
                ->whereIn(
                    'competency_id',
                    $competencyIds,
                )
                ->get([
                    'competency_id',
                    'title',
                    'order_index',
                ]);

        $existingOrders = [];
        $existingTitles = [];

        foreach ($existingPearls as $pearl) {
            $existingOrders[
                $pearl->competency_id
                    .'|'
                    .$pearl->order_index
            ] = true;

            $existingTitles[
                $pearl->competency_id
                    .'|'
                    .$this->normalizeTitle(
                        $pearl->title,
                    )
            ] = true;
        }

        $csvOrders = [];
        $csvTitles = [];
        $errors = [];
        $rows = [];

        foreach ($records as $record) {
            $rowNumber = $record['row_number'];
            $values = $record['values'];

            $values['competency_code'] =
                mb_strtoupper(
                    $values['competency_code'],
                );

            $validator = Validator::make(
                $values,
                [
                    'competency_code' => [
                        'required',
                        'string',
                        'max:30',
                    ],

                    'title' => [
                        'required',
                        'string',
                        'max:200',
                    ],

                    'mnemonic_summary' => [
                        'required',
                        'string',
                        'max:2000',
                    ],

                    'lesson_content' => [
                        'required',
                        'string',
                        'max:30000',
                    ],

                    'learning_objectives' => [
                        'required',
                        'string',
                        'max:5000',
                    ],

                    'key_points' => [
                        'required',
                        'string',
                        'max:5000',
                    ],

                    'common_pitfalls' => [
                        'nullable',
                        'string',
                        'max:5000',
                    ],

                    'recall_prompts' => [
                        'required',
                        'string',
                        'max:10000',
                    ],

                    'source_references' => [
                        'required',
                        'string',
                        'max:10000',
                    ],

                    'estimated_read_seconds' => [
                        'required',
                        'integer',
                        'min:30',
                        'max:3600',
                    ],

                    'order_index' => [
                        'required',
                        'integer',
                        'min:1',
                        'max:65535',
                    ],

                    'rights_status' => [
                        'required',
                        Rule::in([
                            MicroPearl::RIGHTS_PENDING,
                            MicroPearl::RIGHTS_ORIGINAL,
                            MicroPearl::RIGHTS_AUTHORIZED,
                            MicroPearl::RIGHTS_PUBLIC_DOMAIN,
                        ]),
                    ],

                    'is_ai_generated' => [
                        'required',
                        Rule::in(['0', '1']),
                    ],

                    'ai_enrichment_allowed' => [
                        'required',
                        Rule::in(['0', '1']),
                    ],

                    'generation_provider' => [
                        'nullable',
                        'required_if:is_ai_generated,1',
                        'string',
                        'max:100',
                    ],

                    'generation_model' => [
                        'nullable',
                        'required_if:is_ai_generated,1',
                        'string',
                        'max:100',
                    ],

                    'prompt_version' => [
                        'nullable',
                        'required_if:is_ai_generated,1',
                        'string',
                        'max:100',
                    ],
                ],
            );

            if ($validator->fails()) {
                foreach (
                    $validator->errors()->all() as $message
                ) {
                    $errors[] =
                        "CSV row {$rowNumber}: {$message}";
                }

                continue;
            }

            $competency = $competencies->get(
                $values['competency_code'],
            );

            if (! $competency) {
                $errors[] =
                    "CSV row {$rowNumber}: competency_code "
                    ."{$values['competency_code']} does not exist.";

                continue;
            }

            $objectives = $this->splitList(
                $values['learning_objectives'],
            );

            $keyPoints = $this->splitList(
                $values['key_points'],
            );

            $pitfalls = $this->splitList(
                $values['common_pitfalls'],
            );

            $references = $this->splitList(
                $values['source_references'],
            );

            $prompts = $this->splitList(
                $values['recall_prompts'],
            );

            if ($objectives === []) {
                $errors[] =
                    "CSV row {$rowNumber}: provide at least one learning objective.";
            }

            if ($keyPoints === []) {
                $errors[] =
                    "CSV row {$rowNumber}: provide at least one key point.";
            }

            if ($references === []) {
                $errors[] =
                    "CSV row {$rowNumber}: provide at least one source reference.";
            }

            if ($prompts === []) {
                $errors[] =
                    "CSV row {$rowNumber}: provide at least one recall prompt.";
            }

            foreach ($prompts as $prompt) {
                $parts = array_map(
                    'trim',
                    explode('|', $prompt, 2),
                );

                if (
                    count($parts) !== 2
                    || $parts[0] === ''
                    || $parts[1] === ''
                ) {
                    $errors[] =
                        "CSV row {$rowNumber}: every recall prompt must use Question | Answer.";

                    break;
                }
            }

            $orderIndex = (int) $values[
                'order_index'
            ];

            $orderKey = $competency->competency_id
                .'|'
                .$orderIndex;

            $titleKey = $competency->competency_id
                .'|'
                .$this->normalizeTitle(
                    $values['title'],
                );

            if (isset($existingOrders[$orderKey])) {
                $errors[] =
                    "CSV row {$rowNumber}: order_index {$orderIndex} is already used for {$values['competency_code']}.";
            }

            if (isset($csvOrders[$orderKey])) {
                $errors[] =
                    "CSV row {$rowNumber}: duplicates another CSV row's competency and order_index.";
            }

            if (isset($existingTitles[$titleKey])) {
                $errors[] =
                    "CSV row {$rowNumber}: a Micro Pearl with this title already exists for {$values['competency_code']}.";
            }

            if (isset($csvTitles[$titleKey])) {
                $errors[] =
                    "CSV row {$rowNumber}: duplicates another CSV row's title for this competency.";
            }

            $csvOrders[$orderKey] = true;
            $csvTitles[$titleKey] = true;

            $isAiGenerated =
                $values['is_ai_generated'] === '1';

            $rows[] = [
                'preview' => [
                    'row_number' => $rowNumber,

                    'competency_code' => $values['competency_code'],

                    'title' => $values['title'],

                    'order_index' => $orderIndex,

                    'rights_status' => $values['rights_status'],

                    'is_ai_generated' => $isAiGenerated,
                ],

                'payload' => [
                    'competency_id' => $competency->competency_id,

                    'title' => $values['title'],

                    'mnemonic_summary' => $values['mnemonic_summary'],

                    'lesson_content' => $values['lesson_content'],

                    'learning_objectives_text' => implode(PHP_EOL, $objectives),

                    'key_points_text' => implode(PHP_EOL, $keyPoints),

                    'common_pitfalls_text' => implode(PHP_EOL, $pitfalls),

                    'recall_prompts_text' => implode(PHP_EOL, $prompts),

                    'source_references_text' => implode(PHP_EOL, $references),

                    'estimated_read_seconds' => (int) $values[
                            'estimated_read_seconds'
                        ],

                    'order_index' => $orderIndex,

                    'rights_status' => $values['rights_status'],

                    'is_ai_generated' => $isAiGenerated,

                    'ai_enrichment_allowed' => $values[
                            'ai_enrichment_allowed'
                        ] === '1',

                    'generation_provider' => $values[
                            'generation_provider'
                        ] ?: null,

                    'generation_model' => $values[
                            'generation_model'
                        ] ?: null,

                    'prompt_version' => $values[
                            'prompt_version'
                        ] ?: null,
                ],
            ];
        }

        if ($errors !== []) {
            $this->failValidation($errors);
        }

        return $rows;
    }

    /**
     * @return array<int, array{
     *     row_number: int,
     *     values: array<string, string>
     * }>
     */
    private function readCsv(
        string $absolutePath,
    ): array {
        $contents = file_get_contents(
            $absolutePath,
        );

        if ($contents === false) {
            throw new RuntimeException(
                'The CSV could not be read.',
            );
        }

        if (
            ! mb_check_encoding(
                $contents,
                'UTF-8',
            )
        ) {
            throw ValidationException::withMessages([
                'csv' => [
                    'The CSV must use UTF-8 encoding.',
                ],
            ]);
        }

        $handle = fopen(
            $absolutePath,
            'rb',
        );

        if ($handle === false) {
            throw new RuntimeException(
                'The CSV could not be opened.',
            );
        }

        try {
            $header = fgetcsv(
                $handle,
                0,
                ',',
                '"',
                '',
            );

            if (! is_array($header)) {
                throw ValidationException::withMessages([
                    'csv' => [
                        'The CSV is empty.',
                    ],
                ]);
            }

            $header = array_map(
                fn (mixed $value): string => trim(
                    (string) $value,
                ),
                $header,
            );

            $header[0] = preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $header[0],
            ) ?? $header[0];

            if ($header !== self::HEADERS) {
                throw ValidationException::withMessages([
                    'csv' => [
                        'The CSV header does not match the required Micro Pearl template.',
                    ],
                ]);
            }

            $records = [];
            $csvRow = 1;

            while (
                (
                    $cells = fgetcsv(
                        $handle,
                        0,
                        ',',
                        '"',
                        '',
                    )
                ) !== false
            ) {
                $csvRow++;

                if (
                    collect($cells)->every(
                        fn (mixed $value): bool => blank(
                            $value,
                        ),
                    )
                ) {
                    continue;
                }

                if (
                    count($cells)
                    !== count(self::HEADERS)
                ) {
                    throw ValidationException::withMessages([
                        'csv' => [
                            "CSV row {$csvRow} does not contain the expected number of columns.",
                        ],
                    ]);
                }

                $values = array_combine(
                    self::HEADERS,
                    array_map(
                        fn (mixed $value): string => trim(
                            (string) $value,
                        ),
                        $cells,
                    ),
                );

                if ($values === false) {
                    throw new RuntimeException(
                        'The CSV row could not be mapped.',
                    );
                }

                foreach ($values as $value) {
                    if (str_contains($value, "\0")) {
                        throw ValidationException::withMessages([
                            'csv' => [
                                "CSV row {$csvRow} contains an invalid null character.",
                            ],
                        ]);
                    }
                }

                $records[] = [
                    'row_number' => $csvRow,
                    'values' => $values,
                ];

                if (
                    count($records)
                    > self::MAX_ROWS
                ) {
                    throw ValidationException::withMessages([
                        'csv' => [
                            'A single import may contain no more than '
                                .self::MAX_ROWS
                                .' lessons.',
                        ],
                    ]);
                }
            }
        } finally {
            fclose($handle);
        }

        if ($records === []) {
            throw ValidationException::withMessages([
                'csv' => [
                    'The CSV contains no lesson rows.',
                ],
            ]);
        }

        return $records;
    }

    /**
     * Separate multiple values with:
     *
     * Value one || Value two
     *
     * @return array<int, string>
     */
    private function splitList(
        mixed $value,
    ): array {
        if (blank($value)) {
            return [];
        }

        return collect(
            preg_split(
                '/\s*\|\|\s*/u',
                (string) $value,
            ) ?: [],
        )
            ->map(
                fn (string $item): string => trim(
                    $item,
                ),
            )
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeTitle(
        string $title,
    ): string {
        return mb_strtolower(
            preg_replace(
                '/\s+/u',
                ' ',
                trim($title),
            ) ?? trim($title),
        );
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function failValidation(
        array $errors,
    ): never {
        $total = count($errors);
        $visible = array_slice(
            $errors,
            0,
            25,
        );

        if ($total > 25) {
            $visible[] = '...and '
                .($total - 25)
                .' more issue(s).';
        }

        throw ValidationException::withMessages([
            'csv' => $visible,
        ]);
    }

    private function storeUpload(
        User $uploader,
        UploadedFile $file,
    ): string {
        $directory = 'micro-pearl-imports/'
            .$uploader->user_id;

        $path = $file->storeAs(
            $directory,
            Str::uuid().'.csv',
            'local',
        );

        if (! is_string($path)) {
            throw new RuntimeException(
                'The CSV could not be stored securely.',
            );
        }

        return $path;
    }

    private function assertStoredFileIntact(
        MicroPearlImport $import,
    ): string {
        $path = $import->storage_path;

        if (
            ! filled($path)
            || ! Storage::disk('local')
                ->exists($path)
        ) {
            throw new LogicException(
                'The private uploaded file is missing. Upload the CSV again.',
            );
        }

        $disk = Storage::disk('local');

        $absolutePath = $disk->path($path);

        $hash = hash_file(
            'sha256',
            $absolutePath,
        );

        if (
            $hash === false
            || ! hash_equals(
                $import->file_sha256,
                $hash,
            )
            || $disk->size($path)
                !== (int) $import->file_size
        ) {
            throw new LogicException(
                'The uploaded CSV changed after preview. Upload and preview it again.',
            );
        }

        return $absolutePath;
    }

    private function markFailed(
        MicroPearlImport $import,
    ): void {
        $failed = DB::transaction(
            function () use (
                $import,
            ): ?MicroPearlImport {
                $locked = MicroPearlImport::query()
                    ->whereKey($import->import_id)
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $locked
                    || $locked->status
                        !== MicroPearlImport::STATUS_PREVIEWED
                ) {
                    return $locked;
                }

                $locked->forceFill([
                    'status' => MicroPearlImport::STATUS_FAILED,

                    /*
                     * Do not persist exception text,
                     * database errors, paths or CSV content.
                     */
                    'failure_message' => 'Commit failed. Upload and preview the CSV again.',

                    'lock_version' => (
                        (int) $locked->lock_version
                    ) + 1,
                ])->save();

                return $locked;
            },
        );

        if ($failed) {
            $this->purgeStoredFile($failed);
        }
    }

    private function purgeStoredFile(
        MicroPearlImport $import,
    ): void {
        $path = $import->storage_path;

        if (! filled($path)) {
            return;
        }

        $disk = Storage::disk('local');

        $removed = ! $disk->exists($path)
            || $disk->delete($path);

        if (! $removed) {
            return;
        }

        MicroPearlImport::query()
            ->whereKey($import->import_id)
            ->where('storage_path', $path)
            ->update([
                'storage_path' => null,
            ]);

        $import->storage_path = null;
    }

    private function safeOriginalFilename(
        UploadedFile $file,
    ): string {
        $name = basename(
            str_replace(
                '\\',
                '/',
                $file->getClientOriginalName(),
            ),
        );

        $name = preg_replace(
            '/[\x00-\x1F\x7F]/u',
            '',
            $name,
        ) ?? '';

        return mb_substr(
            $name !== ''
                ? $name
                : 'micro-pearls.csv',
            0,
            255,
        );
    }

    private function assertEditor(
        User $editor,
    ): void {
        if (
            ! in_array(
                $editor->role,
                ['admin', 'faculty'],
                true,
            )
        ) {
            throw new LogicException(
                'Only administrators and faculty may import Micro Pearls.',
            );
        }
    }
}
