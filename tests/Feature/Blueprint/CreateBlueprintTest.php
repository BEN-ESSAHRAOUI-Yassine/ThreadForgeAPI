<?php

use App\Models\User;

test('authenticated user can create a blueprint', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/blueprints', [
        'title' => 'Tech Twitter Style',
        'description' => 'For technical content',
        'rules' => ['Use bullet points', 'Keep it concise'],
        'target_audience' => 'Developers',
        'tone' => 'Professional',
        'max_hashtags' => 3,
        'max_caracteres' => 280,
        'allow_emojis' => true,
        'forbidden_words' => ['hype', 'clickbait'],
        'regles_supplementaires' => 'Always cite sources',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id', 'user_id', 'title', 'description', 'rules',
                'target_audience', 'tone', 'max_hashtags', 'max_caracteres',
                'allow_emojis', 'forbidden_words', 'regles_supplementaires',
                'created_at', 'updated_at',
            ],
        ]);

    expect($response['data']['title'])->toBe('Tech Twitter Style');
    expect($response['data']['user_id'])->toBe($user->id);
});

test('unauthenticated user cannot create a blueprint', function () {
    $response = $this->postJson('/api/blueprints', [
        'title' => 'Tech Twitter Style',
    ]);

    $response->assertStatus(401);
});

test('title is required to create a blueprint', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/blueprints', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

test('rules must be an array if provided', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/blueprints', [
        'title' => 'Test',
        'rules' => 'not-an-array',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['rules']);
});

test('max_hashtags must be an integer', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/blueprints', [
        'title' => 'Test',
        'max_hashtags' => 'not-a-number',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['max_hashtags']);
});
