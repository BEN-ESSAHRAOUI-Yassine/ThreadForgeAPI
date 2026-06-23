<?php

use App\Models\Blueprint;
use App\Models\User;

test('user can list their own blueprints', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    Blueprint::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->withToken($token)->getJson('/api/blueprints');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'created_at', 'updated_at'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

    expect($response['meta']['total'])->toBe(3);
});

test('user cannot see other users blueprints', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    Blueprint::factory()->count(2)->create(['user_id' => $otherUser->id]);
    Blueprint::factory()->create(['user_id' => $user->id]);

    $response = $this->withToken($token)->getJson('/api/blueprints');

    expect($response['meta']['total'])->toBe(1);
});

test('user can view their own blueprint detail', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $blueprint = Blueprint::factory()->create(['user_id' => $user->id]);

    $response = $this->withToken($token)->getJson("/api/blueprints/{$blueprint->id}");

    $response->assertStatus(200)
        ->assertJson(['data' => ['id' => $blueprint->id]]);
});

test('user cannot view another users blueprint detail', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $blueprint = Blueprint::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->withToken($token)->getJson("/api/blueprints/{$blueprint->id}");

    $response->assertStatus(403);
});

test('blueprints are paginated', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    Blueprint::factory()->count(20)->create(['user_id' => $user->id]);

    $response = $this->withToken($token)->getJson('/api/blueprints?page=2');

    $response->assertStatus(200);
    expect($response['meta']['current_page'])->toBe(2);
});
