<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionBookmark;
use App\Models\User;

class BookmarkService
{
    public function add(
        User $user,
        Question $question
    ): QuestionBookmark {
        return QuestionBookmark::query()->firstOrCreate(
            [
                'user_id' => $user->user_id,
                'question_id' => $question->question_id,
            ],
            [
                'personal_notes' => null,
            ]
        );
    }

    public function remove(
        User $user,
        Question $question
    ): void {
        QuestionBookmark::query()
            ->where('user_id', $user->user_id)
            ->where('question_id', $question->question_id)
            ->delete();
    }

    public function isBookmarked(
        User $user,
        Question $question
    ): bool {
        return QuestionBookmark::query()
            ->where('user_id', $user->user_id)
            ->where('question_id', $question->question_id)
            ->exists();
    }

    public function updateNotes(
        QuestionBookmark $bookmark,
        ?string $notes
    ): void {
        $notes = $notes !== null
            ? trim($notes)
            : null;

        $bookmark->personal_notes =
            $notes === '' ? null : $notes;

        $bookmark->save();
    }
}
