<?php

namespace Tests\Unit;

use App\Services\PhpDependencyAnalyzer;
use PHPUnit\Framework\TestCase;

class PhpDependencyAnalyzerTest extends TestCase
{
    public function test_it_finds_a_method_calling_a_changed_method(): void
    {
        $code = <<<'PHP'
<?php

class InvoiceService
{
    public function calculateTotal(): float
    {
        return 100;
    }

    public function calculateInvoice(): float
    {
        return $this->calculateTotal();
    }
}
PHP;

        $analyzer = new PhpDependencyAnalyzer();

        $callers = $analyzer->findCallers(
            $code,
            ['calculateTotal']
        );

        $this->assertContains('calculateInvoice', $callers);
    }

    public function test_it_finds_static_method_callers(): void
    {
        $code = <<<'PHP'
<?php

class InvoiceService
{
    public function calculateInvoice(): float
    {
        return TaxCalculator::calculateTotal();
    }
}

class TaxCalculator
{
    public static function calculateTotal(): float
    {
        return 100;
    }
}
PHP;

        $analyzer = new PhpDependencyAnalyzer();

        $callers = $analyzer->findCallers(
            $code,
            ['calculateTotal']
        );

        $this->assertContains('calculateInvoice', $callers);
    }

    public function test_it_returns_no_callers_when_no_target_is_called(): void
    {
        $code = <<<'PHP'
<?php

class InvoiceService
{
    public function calculateInvoice(): float
    {
        return 100;
    }
}
PHP;

        $analyzer = new PhpDependencyAnalyzer();

        $callers = $analyzer->findCallers(
            $code,
            ['calculateTotal']
        );

        $this->assertSame([], $callers);
    }

    public function test_it_returns_no_callers_for_an_empty_target_list(): void
    {
        $analyzer = new PhpDependencyAnalyzer();

        $callers = $analyzer->findCallers(
            '<?php class Example {}',
            []
        );

        $this->assertSame([], $callers);
    }

    
public function test_it_documents_name_based_matching_across_classes(): void
{
    $code = <<<'PHP'
<?php

class InvoiceService
{
    public function calculateInvoice(): float
    {
        return $this->calculateTotal();
    }
}

class UnrelatedReport
{
    public function calculateTotal(): float
    {
        return 10;
    }

    public function generateReport(): float
    {
        return $this->calculateTotal();
    }
}
PHP;

    $analyzer = new PhpDependencyAnalyzer();

    $callers = $analyzer->findCallers(
        $code,
        ['calculateTotal']
    );

    $this->assertContains('calculateInvoice', $callers);
    $this->assertContains('generateReport', $callers);
}

}
