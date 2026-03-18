<?php

namespace App\Enums;

enum UserRole: string
{
    case Teacher = 'teacher';
    case Student = 'student';

    public function label(): string
    {
        return match ($this) {
            UserRole::Teacher => 'Teacher',
            UserRole::Student => 'Student',
        };
    }

    public function isTeacher(): bool
    {
        return $this === UserRole::Teacher;
    }

    public function isStudent(): bool
    {
        return $this === UserRole::Student;
    }
}
