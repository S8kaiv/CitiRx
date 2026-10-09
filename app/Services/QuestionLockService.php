<?php

namespace App\Services;

use App\Models\Question;

class QuestionLockService
{
    public function isLocked(
        Question $question,
    ): bool {
        return $question->contentIsFrozen();
    }

    public function lockReason(
        Question $question,
    ): ?string {
        if (
            $question->research_validated_at !== null
            || $question->research_validated_by
                !== null
        ) {
            return 'This question contains research '
                .'validation metadata and is frozen.';
        }

        if (
            $question->first_served_at !== null
        ) {
            return 'This question was first served on '
                .$question->first_served_at->format(
                    'M j, Y g:i A',
                )
                .'. Its content is frozen to preserve '
                .'historical assessment validity.';
        }

        return null;
    }

    public function canReactivate(
        Question $question,
    ): bool {
        return ! $this->isLocked(
            $question,
        );
    }
}
