<?php

use App\Models\Blueprint;
use App\Models\GeneratedPost;
use App\Models\RawContent;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->blueprint = Blueprint::factory()->create(['user_id' => $this->user->id]);
    $this->token = $this->user->createToken('api-token')->plainTextToken;
});

test('generated post resource structure is correct', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
    ]);
    $generatedPost = GeneratedPost::factory()->create([
        'raw_content_id' => $rawContent->id,
        'hook_propose' => 'Did you know Laravel 13 is amazing?',
        'body_points' => ['Point A', 'Point B'],
        'technical_readability_score' => 80,
        'suggested_hashtags' => ['#Laravel', '#PHP'],
        'tone_compliance_justification' => 'Professional tone maintained.',
    ]);

    $response = $this->withToken($this->token)
        ->getJson("/api/generated-posts/{$generatedPost->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'raw_content_id',
                'hook_propose',
                'body_points',
                'technical_readability_score',
                'suggested_hashtags',
                'tone_compliance_justification',
                'statut',
                'posted_at',
                'created_at',
                'updated_at',
            ],
        ]);

    expect($response['data']['hook_propose'])->toBe('Did you know Laravel 13 is amazing?');
});

test('generated post resource includes raw content from ownership check', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
    ]);
    $generatedPost = GeneratedPost::factory()->create([
        'raw_content_id' => $rawContent->id,
    ]);

    $response = $this->withToken($this->token)
        ->getJson("/api/generated-posts/{$generatedPost->id}");

    expect($response['data'])->toHaveKey('raw_content')
        ->and($response['data']['raw_content'])->toHaveKeys(['id', 'title', 'statut']);
});

test('generated post list returns paginated results', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
    ]);
    GeneratedPost::factory()->count(20)->create([
        'raw_content_id' => $rawContent->id,
    ]);

    $response = $this->withToken($this->token)
        ->getJson('/api/generated-posts');

    expect($response['meta']['total'])->toBe(20)
        ->and(count($response['data']))->toBe(15);
});
