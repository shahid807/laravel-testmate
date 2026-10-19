<?php

namespace Tests\Unit;

use App\Services\TestSelectionService;
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

        $service = app(\App\Services\TestSelectionService::class);
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

}
