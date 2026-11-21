<?php

namespace App\Services;

use App\Models\StudentNameRequest;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentNameService
{
    public const FIELDS = ['first_name', 'middle_name', 'last_name'];

    public function __construct(protected AdminAuditService $audit) {}

    public function request(User $student, array $names): void
    {
        abort_unless($student->role === 'student', 403);
        DB::transaction(function () use ($student, $names): void {
            $student = User::query()->lockForUpdate()->findOrFail($student->user_id);
            abort_unless($student->role === 'student', 403);
            $names = $this->normalize($names);
            if ($names === $this->names($student)) {
                return;
            }
            $pending = StudentNameRequest::query()->where('pending_user_id', $student->user_id)->first();
            if ($pending && $pending->requested_names === $names) {
                return;
            }
            if ($pending) {
                $pending->update(['status' => 'superseded', 'pending_user_id' => null]);
            }
            StudentNameRequest::query()->create([
                'user_id' => $student->user_id,
                'pending_user_id' => $student->user_id,
                'original_names' => $this->names($student),
                'requested_names' => $names,
                'status' => 'pending',
            ]);
        });
    }

    public function decide(User $dean, StudentNameRequest $request, bool $approve, ?string $note): void
    {
        abort_unless($dean->role === 'dean', 403);
        DB::transaction(function () use ($dean, $request, $approve, $note): void {
            $student = User::query()->lockForUpdate()->findOrFail($request->user_id);
            $request = StudentNameRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($student->role !== 'student' || $request->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'This request is no longer available. Refresh the page.']);
            }
            if ($approve && $request->original_names !== $this->names($student)) {
                throw ValidationException::withMessages(['decision' => 'The official name has changed. Ask the student to submit a fresh request.']);
            }
            if ($approve) {
                $student->forceFill($request->requested_names)->save();
            }
            $request->update([
                'status' => $approve ? 'approved' : 'declined', 'pending_user_id' => null,
                'decided_by' => $dean->user_id, 'decided_at' => now(), 'decision_note' => $note,
            ]);
            $this->audit->record($dean, 'student_name.'.$request->status, $student,
                ['name_request' => ['old' => $request->original_names, 'new' => $request->requested_names]], $note);
        });
    }

    public function edit(User $dean, User $student, array $names): void
    {
        abort_unless($dean->role === 'dean', 403);
        DB::transaction(function () use ($dean, $student, $names): void {
            $student = User::query()->lockForUpdate()->findOrFail($student->user_id);
            abort_unless($student->role === 'student', 404);
            $old = $this->names($student);
            if ($old === $this->normalize($names)) {
                return;
            }
            $student->forceFill($this->normalize($names))->save();
            StudentNameRequest::query()->where('pending_user_id', $student->user_id)->update([
                'status' => 'superseded', 'pending_user_id' => null, 'decided_by' => $dean->user_id,
                'decided_at' => now(), 'decision_note' => 'The Dean updated the official name directly.',
            ]);
            $this->audit->record($dean, 'student_name.edited', $student,
                ['official_name' => ['old' => $old, 'new' => $this->names($student)]]);
        });
    }

    public function names(User $student): array
    {
        return $this->normalize(Arr::only($student->getAttributes(), self::FIELDS));
    }

    private function normalize(array $names): array
    {
        return [
            'first_name' => trim((string) ($names['first_name'] ?? '')),
            'middle_name' => filled($names['middle_name'] ?? null) ? trim($names['middle_name']) : null,
            'last_name' => trim((string) ($names['last_name'] ?? '')),
        ];
    }
}
