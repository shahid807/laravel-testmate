<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use PhpParser\Error;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

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
     * Find methods or named functions whose bodies were added, removed,
     * or changed.
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
                ! array_key_exists($name, $originalMethods)
                || ! array_key_exists($name, $updatedMethods)
                || $originalMethods[$name] !== $updatedMethods[$name]
            ) {
                $changed[] = $name;
            }
        }

        return $changed;
    }

    /**
     * Extract method and named-function bodies using PHP AST parsing.
     *
     * @return array<string, string>
     */
    private function extractMethods(string $code): array
    {
        try {
            $parser = (new ParserFactory)
                ->createForNewestSupportedVersion();

            $ast = $parser->parse($code);

            if ($ast === null) {
                return [];
            }

            $finder = new NodeFinder;
            $printer = new Standard;

            $nodes = [
                ...$finder->findInstanceOf($ast, ClassMethod::class),
                ...$finder->findInstanceOf($ast, Function_::class),
            ];

            $methods = [];

            foreach ($nodes as $node) {
                $name = $node->name->toString();

                // Abstract methods and declarations without bodies.
                if ($node->stmts === null) {
                    $body = '';
                } else {
                    $body = $printer->prettyPrint($node->stmts);
                }

                $methods[$name] = $body;
            }

            return $methods;
        } catch (Error) {
            // Invalid or incomplete PHP input should not crash the request.
            return [];
        }
    }
}
