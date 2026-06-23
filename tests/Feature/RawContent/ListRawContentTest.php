<?php

use App\Models\Blueprint;
use App\Models\RawContent;
use App\Models\User;

test('user can list their own raw contents', function () {
    $user = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('api-token')->plainTextToken;

    RawContent::factory()->count(3)->create([
        'user_id' => $user->id,
        'blueprint_id' => $blueprint->id,
    ]);

    $response = $this->withToken($token)->getJson('/api/raw-contents');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'statut', 'created_at'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

    expect($response['meta']['total'])->toBe(3);
});

test('user cannot see other users raw contents', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('api-token')->plainTextToken;

    RawContent::factory()->count(2)->create([
        'user_id' => $otherUser->id,
        'blueprint_id' => $blueprint->id,
    ]);
    RawContent::factory()->create([
        'user_id' => $user->id,
        'blueprint_id' => $blueprint->id,
    ]);

    $response = $this->withToken($token)->getJson('/api/raw-contents');

    expect($response['meta']['total'])->toBe(1);
});

test('user can view their own raw content detail', function () {
    $user = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('api-token')->plainTextToken;

    $rawContent = RawContent::factory()->create([
        'user_id' => $user->id,
        'blueprint_id' => $blueprint->id,
    ]);

    $response = $this->withToken($token)->getJson("/api/raw-contents/{$rawContent->id}");

    $response->assertStatus(200)
        ->assertJson(['data' => ['id' => $rawContent->id]]);
});

test('user cannot view another users raw content', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['user_id' => $otherUser->id]);
    $token = $user->createToken('api-token')->plainTextToken;

    $rawContent = RawContent::factory()->create([
        'user_id' => $otherUser->id,
        'blueprint_id' => $blueprint->id,
    ]);

    $response = $this->withToken($token)->getJson("/api/raw-contents/{$rawContent->id}");

    $response->assertStatus(403);
});
