<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class QuestionChoice extends Model
{
    use HasUuids;

    protected $table = 'question_choices';

    protected $primaryKey = 'choice_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'question_id',
        'choice_letter',
        'choice_text',
        'is_correct',
        'distractor_fallacy_note',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(
            Question::class,
            'question_id',
            'question_id',
        );
    }

    protected static function booted(): void
    {
        static::saving(
            function (
                QuestionChoice $choice,
            ): void {
                if (
                    ! $choice->isDirty([
                        'choice_text',
                        'is_correct',
                        'distractor_fallacy_note',
                    ])
                ) {
                    return;
                }

                self::assertQuestionIsEditable(
                    $choice,
                );
            },
        );

        static::deleting(
            function (
                QuestionChoice $choice,
            ): void {
                self::assertQuestionIsEditable(
                    $choice,
                );
            },
        );
    }

    private static function assertQuestionIsEditable(
        QuestionChoice $choice,
    ): void {
        $isFrozen = Question::query()
            ->whereKey(
                $choice->question_id,
            )
            ->where(
                function ($query): void {
                    $query
                        ->whereNotNull(
                            'first_served_at',
                        )
                        ->orWhereNotNull(
                            'research_validated_at',
                        )
                        ->orWhereNotNull(
                            'research_validated_by',
                        );
                },
            )
            ->exists();

        if ($isFrozen) {
            throw new LogicException(
                'Choices belonging to a served '
                    .'or validated question cannot '
                    .'be changed.',
            );
        }
    }
}
