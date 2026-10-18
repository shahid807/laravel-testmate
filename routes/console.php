<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('openrouter:test', function () {
    $key = config('services.openrouter.key');

    if (! $key) {
        $this->error('OpenRouter API key is missing.');

        return;
    }

    $response = Http::withToken($key)
        ->timeout(60)
        ->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => config('services.openrouter.model'),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Reply with exactly: OpenRouter connected',
                ],
            ],
        ]);

    if ($response->successful()) {
        $this->info('API request successful!');
        $this->line(
            data_get($response->json(), 'choices.0.message.content')
                ?? 'Response received, but no text was returned.'
        );
    } else {
        $this->error('API request failed: HTTP '.$response->status());
        $this->line($response->json('error.message') ?? 'Check API key, model availability, and account limits.');
    }
})->purpose('Test the OpenRouter API connection');
