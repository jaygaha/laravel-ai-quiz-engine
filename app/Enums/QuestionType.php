<?php

namespace App\Enums;

enum QuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case TrueFalse = 'true_false';
    case ShortAnswer = 'short_answer';

    public function label(): string
    {
        return match ($this) {
            QuestionType::MultipleChoice => 'Multiple Choice',
            QuestionType::TrueFalse => 'True / False',
            QuestionType::ShortAnswer => 'Short Answer',
        };
    }

    public function hasOptions(): bool
    {
        return $this === QuestionType::MultipleChoice || $this === QuestionType::TrueFalse;
    }
}
