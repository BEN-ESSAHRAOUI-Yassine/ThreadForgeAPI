<?php

use App\Models\Blueprint;
use App\Models\User;

test('user can delete their own blueprint', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $blueprint = Blueprint::factory()->create(['user_id' => $user->id]);

    $response = $this->withToken($token)->deleteJson("/api/blueprints/{$blueprint->id}");

    $response->assertStatus(204);
    expect(Blueprint::count())->toBe(0);
});

test('user cannot delete another users blueprint', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $blueprint = Blueprint::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->withToken($token)->deleteJson("/api/blueprints/{$blueprint->id}");

    $response->assertStatus(403);
    expect(Blueprint::count())->toBe(1);
});

test('unauthenticated user cannot delete a blueprint', function () {
    $blueprint = Blueprint::factory()->create();

    $response = $this->deleteJson("/api/blueprints/{$blueprint->id}");

    $response->assertStatus(401);
});
