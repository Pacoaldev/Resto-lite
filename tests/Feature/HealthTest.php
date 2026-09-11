<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

it('GET /api/health reporta ok cuando DB responde', function () {
    // Redis puede no estar disponible en CI local — aceptamos ok o degraded
    $response = $this->getJson('/api/health');

    $response->assertJsonStructure(['status', 'checks' => ['database', 'redis'], 'timestamp']);
    expect($response->json('checks.database'))->toBe('ok');
    expect(in_array($response->json('status'), ['ok', 'degraded'], true))->toBeTrue();
});

it('GET /api/health marca degraded si Redis falla', function () {
    Redis::shouldReceive('connection')->andThrow(new RuntimeException('redis down'));

    $this->getJson('/api/health')
        ->assertOk()
        ->assertJsonPath('status', 'degraded')
        ->assertJsonPath('checks.database', 'ok')
        ->assertJsonPath('checks.redis', 'fail');
});
