<?php

namespace App\Services;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

class CodeDiffService
{
    public function generate(string $originalCode, string $updatedCode): string
    {
        if ($originalCode === $updatedCode) {
            return '';
        }

        $differ = new Differ(
            new UnifiedDiffOutputBuilder(
                "--- Original Code\n+++ Updated Code\n"
            )
        );

        return $differ->diff($originalCode, $updatedCode);
    }
}
