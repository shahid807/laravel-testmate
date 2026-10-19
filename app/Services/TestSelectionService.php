<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class TestSelectionService
{
    /**
     * Identify changed PHP methods and recommend related test files.
     *
     * @return array{
     *     changed_methods: array<int, string>,
     *     recommended_tests: array<int, array{
     *         file: string,
     *         score: int,
     *         reason: string
     *     }>
     * }
     */
    public function select(string $originalCode, string $updatedCode): array
    {
        $changedMethods = $this->identifyChangedMethods(
            $originalCode,
            $updatedCode
        );

        $testFiles = File::allFiles(base_path('tests'));
        $recommendations = [];

        foreach ($testFiles as $file) {
            $path = $file->getPathname();

            if (! str_ends_with($file->getFilename(), 'Test.php')) {
                continue;
            }

            $content = File::get($path);
            $score = 0;
            $reasons = [];

            foreach ($changedMethods as $method) {
                $methodName = strtolower($method);

                if (str_contains(strtolower($file->getFilename()), $methodName)) {
                    $score += 5;
                    $reasons[] = "Test filename matches method '{$method}'.";
                }

                if (str_contains(strtolower($content), $methodName)) {
                    $score += 3;
                    $reasons[] = "Test content references method '{$method}'.";
                }
            }

            if ($score > 0) {
                $recommendations[] = [
                    'file' => str_replace(
                        base_path().DIRECTORY_SEPARATOR,
                        '',
                        $path
                    ),
                    'score' => $score,
                    'reason' => implode(' ', array_unique($reasons)),
                ];
            }
        }

        usort(
            $recommendations,
            fn (array $a, array $b): int => $b['score'] <=> $a['score']
        );

        return [
            'changed_methods' => $changedMethods,
            'recommended_tests' => $recommendations,
        ];
    }

    /**
     * Find method names whose method bodies were added, removed, or changed.
     *
     * This lightweight implementation is intended for simple PHP methods.
     *
     * @return array<int, string>
     */
    private function identifyChangedMethods(
        string $originalCode,
        string $updatedCode
    ): array {
        $originalMethods = $this->extractMethods($originalCode);
        $updatedMethods = $this->extractMethods($updatedCode);

        $methodNames = array_unique([
            ...array_keys($originalMethods),
            ...array_keys($updatedMethods),
        ]);

        $changed = [];

        foreach ($methodNames as $name) {
            if (
                ! isset($originalMethods[$name])
                || ! isset($updatedMethods[$name])
                || $originalMethods[$name] !== $updatedMethods[$name]
            ) {
                $changed[] = $name;
            }
        }

        return $changed;
    }

    /**
     * Extract method bodies using a basic regular expression.
     *
     * Note: Regex is not a full PHP parser and may not handle every syntax.
     *
     * @return array<string, string>
     */
    private function extractMethods(string $code): array
    {
        preg_match_all(
            '/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\([^)]*\)\s*(?::\s*[^{]+)?\s*\{/s',
            $code,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        $methods = [];

        foreach ($matches[1] as $index => $match) {
            $name = $match[0];
            $openingBrace = strpos(
                $code,
                '{',
                $matches[0][$index][1]
            );

            if ($openingBrace === false) {
                continue;
            }

            $depth = 0;
            $length = strlen($code);

            for ($i = $openingBrace; $i < $length; $i++) {
                if ($code[$i] === '{') {
                    $depth++;
                } elseif ($code[$i] === '}') {
                    $depth--;

                    if ($depth === 0) {
                        $methods[$name] = substr(
                            $code,
                            $openingBrace,
                            $i - $openingBrace + 1
                        );

                        break;
                    }
                }
            }
        }

        return $methods;
    }
}
