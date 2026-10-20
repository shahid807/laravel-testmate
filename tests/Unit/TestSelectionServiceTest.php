<?php

namespace Tests\Unit;

use App\Services\TestSelectionService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TestSelectionServiceTest extends TestCase
{
    public function test_it_identifies_a_changed_method(): void
    {
        $originalCode = <<<'PHP'
<?php

class Calculator
{
    public function calculateTotal(float $price): float
    {
        return $price * 2;
    }
}
PHP;

        $updatedCode = <<<'PHP'
<?php

class Calculator
{
    public function calculateTotal(float $price): float
    {
        return $price + 2;
    }
}
PHP;

        $service = app(TestSelectionService::class);

        $result = $service->select($originalCode, $updatedCode);

        $this->assertContains(
            'calculateTotal',
            $result['changed_methods']
        );
    }

    public function test_it_does_not_report_unchanged_methods(): void
    {
        $code = <<<'PHP'
<?php

class Calculator
{
    public function calculateTotal(float $price): float
    {
        return $price * 2;
    }
}
PHP;

        $service = app(TestSelectionService::class);

        $result = $service->select($code, $code);

        $this->assertSame([], $result['changed_methods']);
    }

    public function test_it_recommends_a_test_file_referencing_a_changed_method(): void
    {
        $testDirectory = base_path('tests/Unit');
        $temporaryFile = $testDirectory.'/CalculatorTest.php';

        file_put_contents(
            $temporaryFile,
            '<?php class CalculatorTest { public function test_calculateTotal() {} }'
        );

        try {
            $originalCode = <<<'PHP'
<?php
class Calculator
{
    public function calculateTotal(float $price): float
    {
        return $price * 2;
    }
}
PHP;

            $updatedCode = <<<'PHP'
<?php
class Calculator
{
    public function calculateTotal(float $price): float
    {
        return $price + 2;
    }
}
PHP;

            $service = app(TestSelectionService::class);
            $result = $service->select($originalCode, $updatedCode);

            $recommendedFiles = array_column(
                $result['recommended_tests'],
                'file'
            );

            $this->assertContains(
                'tests'.DIRECTORY_SEPARATOR.'Unit'.DIRECTORY_SEPARATOR.'CalculatorTest.php',
                $recommendedFiles
            );
        } finally {
            if (file_exists($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    public function test_it_detects_changes_when_method_contains_braces_in_strings(): void
    {
        $originalCode = <<<'PHP'
<?php

class Example
{
    public function getText(): string
    {
        return '}';
    }
}
PHP;

        $updatedCode = <<<'PHP'
<?php

class Example
{
    public function getText(): string
    {
        return '{';
    }
}
PHP;

        $service = app(TestSelectionService::class);

        $result = $service->select($originalCode, $updatedCode);

        $this->assertContains('getText', $result['changed_methods']);
    }

    public function test_it_finds_dependency_callers_and_returns_them_in_results(): void
    {
        $fixturePath = app_path(
            'Services/DependencyFixture'.uniqid().'.php'
        );

        $fixtureCode = <<<'PHP'
<?php

class DependencyFixture
{
    public function calculateInvoice(): float
    {
        return $this->calculateTotal();
    }

    public function calculateTotal(): float
    {
        return 100;
    }
}
PHP;

        File::put(
            $fixturePath,
            $fixtureCode
        );

        try {
            $service = app(
                TestSelectionService::class
            );

            $originalCode = <<<'PHP'
<?php

class InvoiceService
{
    public function calculateTotal(): int
    {
        return 50;
    }

    public function unrelatedMethod(): string
    {
        return 'unchanged';
    }
}
PHP;

            $updatedCode = <<<'PHP'
<?php

class InvoiceService
{
    public function calculateTotal(): int
    {
        return 100;
    }

    public function unrelatedMethod(): string
    {
        return 'unchanged';
    }
}
PHP;

            $result = $service->select(
                $originalCode,
                $updatedCode
            );

            $this->assertContains(
                'calculateInvoice',
                $result['dependency_callers']
            );

            $this->assertArrayHasKey(
                'recommended_tests',
                $result
            );
        } finally {
            File::delete($fixturePath);
        }
    }

    public function test_it_detects_a_return_type_change(): void
    {
        $originalCode = <<<'PHP'
<?php

class Calculator
{
    public function calculateTotal(float $price): int
    {
        return 100;
    }
}
PHP;

        $updatedCode = <<<'PHP'
<?php

class Calculator
{
    public function calculateTotal(float $price): float
    {
        return 100;
    }
}
PHP;

        $service = app(TestSelectionService::class);

        $result = $service->select($originalCode, $updatedCode);

        $this->assertContains(
            'calculateTotal',
            $result['changed_methods']
        );
    }
}
