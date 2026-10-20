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
    public function __construct(
        private readonly PhpDependencyAnalyzer $dependencyAnalyzer
    ) {}

    /**
     * Identify changed methods, their callers, and related tests.
     *
     * @return array{
     *     changed_methods: array<int, string>,
     *     dependency_callers: array<int, string>,
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

        $dependencyCallers = $this->findProjectCallers($changedMethods);

        $testFiles = File::allFiles(base_path('tests'));
        $recommendations = [];

        foreach ($testFiles as $file) {
            if (! str_ends_with($file->getFilename(), 'Test.php')) {
                continue;
            }

            $path = $file->getPathname();
            $content = File::get($path);
            $score = 0;
            $reasons = [];

            foreach ($changedMethods as $method) {
                $this->addMethodEvidence(
                    $method,
                    $file->getFilename(),
                    $content,
                    $score,
                    $reasons,
                    5,
                    3,
                    'Changed method'
                );
            }

            foreach ($dependencyCallers as $caller) {
                $this->addMethodEvidence(
                    $caller,
                    $file->getFilename(),
                    $content,
                    $score,
                    $reasons,
                    4,
                    2,
                    'Dependency caller'
                );
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
            fn (array $a, array $b): int =>
                $b['score'] <=> $a['score']
        );

        return [
            'changed_methods' => $changedMethods,
            'dependency_callers' => $dependencyCallers,
            'recommended_tests' => $recommendations,
        ];
    }

    /**
     * Find project methods that call changed methods.
     *
     * This is name-based analysis, not complete type resolution.
     *
     * @param array<int, string> $changedMethods
     * @return array<int, string>
     */
    private function findProjectCallers(array $changedMethods): array
    {
        if ($changedMethods === []) {
            return [];
        }

        $callers = [];

        foreach (File::allFiles(base_path('app')) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $source = File::get($file->getPathname());

            $callers = [
                ...$callers,
                ...$this->dependencyAnalyzer->findCallers(
                    $source,
                    $changedMethods
                ),
            ];
        }

        $changedNames = array_map('strtolower', $changedMethods);

        $callers = array_filter(
            array_unique($callers),
            fn (string $caller): bool =>
                ! in_array(strtolower($caller), $changedNames, true)
        );

        return array_values($callers);
    }

    /**
     * Add filename/content matching evidence to a test's score.
     *
     * @param array<int, string> $reasons
     */
    private function addMethodEvidence(
        string $method,
        string $filename,
        string $content,
        int &$score,
        array &$reasons,
        int $filenamePoints,
        int $contentPoints,
        string $evidenceType
    ): void {
        $methodName = strtolower($method);

        if (str_contains(strtolower($filename), $methodName)) {
            $score += $filenamePoints;
            $reasons[] = "{$evidenceType} '{$method}' matches the test filename.";
        }

        if (str_contains(strtolower($content), $methodName)) {
            $score += $contentPoints;
            $reasons[] = "{$evidenceType} '{$method}' appears in test content.";
        }
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

                if ($node->stmts === null) {
                    $body = '';
                } else {
                    $body = $printer->prettyPrint($node->stmts);
                }

                $methods[$name] = $body;
            }

            return $methods;
        } catch (Error) {
            return [];
        }
    }
}
