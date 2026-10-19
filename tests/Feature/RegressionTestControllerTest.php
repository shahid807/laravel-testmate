<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegressionTestControllerTest extends TestCase
{
    public function test_regression_page_loads(): void
    {
        $response = $this->get('/regression');

        $response->assertOk();
        $response->assertViewIs('regression');
    }

    public function test_regression_analysis_uses_mocked_ai_response(): void
    {
        config([
            'services.openrouter.key' => 'test-key',
            'services.openrouter.model' => 'openrouter/free',
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'The calculation changed. Rerun total calculation tests.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->post('/regression/analyze', [
            'original_code' => 'return $price * $quantity;',
            'updated_code' => 'return $price + $quantity;',
        ]);

        $response->assertOk();
        $response->assertViewIs('regression');
        $response->assertViewHas(
            'analysis',
            'The calculation changed. Rerun total calculation tests.'
        );

        $response->assertViewHas('diff', function ($diff) {
            return str_contains($diff, '-return $price * $quantity;')
                && str_contains($diff, '+return $price + $quantity;');
        });

        $response->assertSee('Code Changes (Unified Diff)');
        $response->assertSee('-return $price * $quantity;');
        $response->assertSee('+return $price + $quantity;');

        Http::assertSent(function ($request) {
            $message = $request['messages'][1]['content'];

            return str_contains(
                $request->url(),
                '/api/v1/chat/completions'
            )
                && str_contains($message, 'return $price * $quantity;')
                && str_contains($message, 'return $price + $quantity;')
                && str_contains($message, 'UNIFIED DIFF:')
                && str_contains($message, '-return $price * $quantity;')
                && str_contains($message, '+return $price + $quantity;');
        });

    }

    public function test_regression_analysis_requires_both_code_versions(): void
    {
        $response = $this->post('/regression/analyze', [
            'original_code' => 'return 10;',
        ]);

        $response->assertSessionHasErrors(['updated_code']);
    }

    public function test_regression_analysis_handles_missing_api_key(): void
    {
        config(['services.openrouter.key' => null]);

        $response = $this->from('/regression')
            ->post('/regression/analyze', [
                'original_code' => 'return 10;',
                'updated_code' => 'return 20;',
            ]);

        $response->assertRedirect('/regression');
        $response->assertSessionHasErrors(['original_code']);
    }

    public function test_identical_code_produces_empty_diff(): void
    {
        config([
            'services.openrouter.key' => 'test-key',
            'services.openrouter.model' => 'openrouter/free',
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'No code changes detected.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $code = 'return $price * $quantity;';

        $response = $this->post('/regression/analyze', [
            'original_code' => $code,
            'updated_code' => $code,
        ]);

        $response->assertOk();
        $response->assertViewHas('diff', '');

        Http::assertSentCount(0);
    }
}
