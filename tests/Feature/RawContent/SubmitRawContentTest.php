<?php

use App\Models\Blueprint;
use App\Models\User;

test('authenticated user can submit raw content and receive 202', function () {
    $user = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/raw-contents', [
        'title' => 'My Blog Post',
        'contenu_brut' => 'This is the raw content of my blog post about Laravel.',
        'blueprint_id' => $blueprint->id,
    ]);

    $response->assertStatus(202)
        ->assertJsonStructure([
            'data' => [
                'id', 'user_id', 'blueprint_id', 'title', 'contenu_brut',
                'statut', 'created_at', 'updated_at',
            ],
        ]);

    expect($response['data']['statut'])->toBe('pending');
    expect($response['data']['title'])->toBe('My Blog Post');
});

test('unauthenticated user cannot submit raw content', function () {
    $response = $this->postJson('/api/raw-contents', [
        'title' => 'Test',
        'contenu_brut' => 'Content',
        'blueprint_id' => 1,
    ]);

    $response->assertStatus(401);
});

test('title is required', function () {
    $user = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/raw-contents', [
        'contenu_brut' => 'Content',
        'blueprint_id' => $blueprint->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

test('contenu_brut is required', function () {
    $user = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/raw-contents', [
        'title' => 'Test',
        'blueprint_id' => $blueprint->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['contenu_brut']);
});

test('blueprint must belong to the authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['user_id' => $otherUser->id]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/raw-contents', [
        'title' => 'Test',
        'contenu_brut' => 'Content',
        'blueprint_id' => $blueprint->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['blueprint_id']);
});

test('non-existent blueprint returns validation error', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/raw-contents', [
        'title' => 'Test',
        'contenu_brut' => 'Content',
        'blueprint_id' => 99999,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['blueprint_id']);
});

test('content exceeding size limit is rejected', function () {
    $user = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/raw-contents', [
        'title' => 'Test',
        'contenu_brut' => str_repeat('a', 512001),
        'blueprint_id' => $blueprint->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['contenu_brut']);
});
