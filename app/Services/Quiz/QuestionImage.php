<?php

namespace App\Services\Quiz;

class QuestionImage
{
    public static function url(?string $path): ?string
    {
        if (!$path) return null;

        return route('quiz.question-image', ['path' => substr($path, strlen('quiz-questions/'))], false);
    }
}
