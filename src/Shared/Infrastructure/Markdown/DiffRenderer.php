<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Markdown;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

final class DiffRenderer
{
    public function render(string $from, string $to): string
    {
        $builder = new UnifiedDiffOutputBuilder("--- anterior\n+++ actual\n");
        $differ = new Differ($builder);

        return $differ->diff($from, $to);
    }
}
