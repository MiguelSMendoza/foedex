<?php

declare(strict_types=1);

namespace App\Shared\Application;

final class TitleFormatter
{
    public function truncate(string $value, int $maxLength = 60): string
    {
        $clean = trim($value);

        if (mb_strlen($clean) <= $maxLength) {
            return $clean;
        }

        return rtrim(mb_substr($clean, 0, max(1, $maxLength - 3))).'...';
    }
}
