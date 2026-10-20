<?php

namespace App\Services;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

class PhpDependencyAnalyzer
{
    /**
     * Find methods that call the specified target methods.
     *
     * This is a lightweight, name-based dependency analysis.
     * It does not resolve variable types or build a complete
     * inter-file dependency graph.
     *
     * @param  array<int, string>  $targetMethods
     * @return array<int, string>
     */
    public function findCallers(
        string $code,
        array $targetMethods
    ): array {
        if ($targetMethods === []) {
            return [];
        }

        try {
            $parser = (new ParserFactory)
                ->createForNewestSupportedVersion();

            $ast = $parser->parse($code);

            if ($ast === null) {
                return [];
            }

            $finder = new NodeFinder;

            $methods = $finder->findInstanceOf(
                $ast,
                ClassMethod::class
            );

            $functions = $finder->findInstanceOf(
                $ast,
                Function_::class
            );

            $callableNodes = [...$methods, ...$functions];

            $targets = array_fill_keys(
                array_map('strtolower', $targetMethods),
                true
            );

            $callers = [];

            foreach ($callableNodes as $callable) {
                if ($callable->stmts === null) {
                    continue;
                }
                $calls = $this->findCallsInCallable($callable->stmts);

                foreach ($calls as $call) {
                    if (! $call->name instanceof Identifier) {
                        continue;
                    }

                    $calledName = strtolower(
                        $call->name->toString()
                    );

                    if (! isset($targets[$calledName])) {
                        continue;
                    }

                    $callerName = $callable->name->toString();

                    // A method should not count as its own caller.
                    if (strtolower($callerName) === $calledName) {
                        continue;
                    }

                    $callers[] = $callerName;
                }
            }

            return array_values(array_unique($callers));
        } catch (Error) {
            // Incomplete or invalid PHP source should not crash analysis.
            return [];
        }
    }

    /**
     * Find method calls without traversing into nested callables.
     *
     * @param  array<int, Stmt>  $statements
     * @return array<int, MethodCall|StaticCall>
     */
    private function findCallsInCallable(array $statements): array
    {
        $calls = [];

        $walk = function (mixed $value) use (&$walk, &$calls): void {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $walk($item);
                }

                return;
            }

            if (! $value instanceof Node) {
                return;
            }

            // Do not attribute nested callable bodies to the outer method.
            if (
                $value instanceof Closure ||
                $value instanceof ArrowFunction ||
                $value instanceof Function_ ||
                $value instanceof ClassMethod ||
                $value instanceof ClassLike
            ) {
                return;
            }

            if (
                $value instanceof MethodCall ||
                $value instanceof StaticCall
            ) {
                $calls[] = $value;
            }

            foreach ($value->getSubNodeNames() as $name) {
                $walk($value->$name);
            }
        };

        $walk($statements);

        return $calls;
    }
}
