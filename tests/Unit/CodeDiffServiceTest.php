<?php

namespace Tests\Unit;

use App\Services\CodeDiffService;
use PHPUnit\Framework\TestCase;

class CodeDiffServiceTest extends TestCase
{
    public function test_it_identifies_changed_code_lines(): void
    {
        $service = new CodeDiffService;

        $originalCode = '<?php'.PHP_EOL
            .'$total = $price * $quantity;';

        $updatedCode = '<?php'.PHP_EOL
            .'$total = $price + $quantity;';

        $diff = $service->generate($originalCode, $updatedCode);

        $this->assertStringContainsString(
            '-$total = $price * $quantity;',
            $diff
        );

        $this->assertStringContainsString(
            '+$total = $price + $quantity;',
            $diff
        );
    }

    public function test_it_returns_empty_string_when_code_is_unchanged(): void
    {
        $service = new CodeDiffService;

        $code = '<?php'.PHP_EOL.'return 10;';

        $diff = $service->generate($code, $code);

        $this->assertSame('', $diff);
    }
}
