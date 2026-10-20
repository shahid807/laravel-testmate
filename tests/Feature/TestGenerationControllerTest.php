<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TestGenerationControllerTest extends TestCase
{
    public function test_homepage_displays_testmate(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('testmate');
    }

    public function test_generation_uses_mocked_openrouter_response(): void
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
                            'content' => 'Example PHPUnit test suggestion',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $code = '<?php function add($a, $b) { return $a + $b; }';

        $response = $this->post('/generate-tests', [
            'code' => $code,
        ]);

        $response->assertOk();
        $response->assertViewIs('testmate');
        $response->assertViewHas('originalCode', $code);
        $response->assertViewHas(
            'generatedTests',
            'Example PHPUnit test suggestion'
        );

        Http::assertSent(function ($request) {
            return str_contains(
                $request->url(),
                '/api/v1/chat/completions'
            ) && $request->hasHeader(
                'Authorization',
                'Bearer test-key'
            );
        });
    }

    public function test_generation_fails_when_api_key_is_missing(): void
    {
        config(['services.openrouter.key' => null]);

        $response = $this->from('/')
            ->post('/generate-tests', ['code' => 'some PHP code']);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors([
            'code' => 'OpenRouter API key is missing.',
        ]);
    }

    public function test_generation_requires_code(): void
    {
        $response = $this->post('/generate-tests', []);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_generation_handles_openrouter_error(): void
    {
        config([
            'services.openrouter.key' => 'test-key',
            'services.openrouter.model' => 'openrouter/free',
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'error' => ['message' => 'Test API error'],
            ], 502),
        ]);

        $response = $this->from('/')
            ->post('/generate-tests', ['code' => 'some PHP code']);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors([
            'code' => 'Test API error',
        ]);
    }

    public function test_generation_handles_empty_model_response(): void
    {
        config([
            'services.openrouter.key' => 'test-key',
            'services.openrouter.model' => 'openrouter/free',
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [],
            ], 200),
        ]);

        $response = $this->from('/')
            ->post('/generate-tests', ['code' => 'some PHP code']);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors([
            'code' => 'The model returned an empty response.',
        ]);
    }

    public function test_generation_handles_network_exception(): void
    {
        config([
            'services.openrouter.key' => 'test-key',
            'services.openrouter.model' => 'openrouter/free',
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => function () {
                throw new \RuntimeException('Connection failed');
            },
        ]);

        $response = $this->from('/')
            ->post('/generate-tests', ['code' => 'some PHP code']);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors([
            'code' => 'The request failed. Check your connection and try again.',
        ]);
    }
}
