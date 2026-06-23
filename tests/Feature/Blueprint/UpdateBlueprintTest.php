<?php

use App\Models\Blueprint;
use App\Models\User;

test('user can update their own blueprint', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $blueprint = Blueprint::factory()->create([
        'user_id' => $user->id,
        'title' => 'Original Title',
    ]);

    $response = $this->withToken($token)->putJson("/api/blueprints/{$blueprint->id}", [
        'title' => 'Updated Title',
        'tone' => 'Casual',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $blueprint->id,
                'title' => 'Updated Title',
                'tone' => 'Casual',
            ],
        ]);

    expect($blueprint->fresh()->title)->toBe('Updated Title');
});

test('user cannot update another users blueprint', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $blueprint = Blueprint::factory()->create([
        'user_id' => $otherUser->id,
        'title' => 'Original Title',
    ]);

    $response = $this->withToken($token)->putJson("/api/blueprints/{$blueprint->id}", [
        'title' => 'Hacked Title',
    ]);

    $response->assertStatus(403);
    expect($blueprint->fresh()->title)->toBe('Original Title');
});

test('unauthenticated user cannot update a blueprint', function () {
    $blueprint = Blueprint::factory()->create();

    $response = $this->putJson("/api/blueprints/{$blueprint->id}", [
        'title' => 'New Title',
    ]);

    $response->assertStatus(401);
});
