<?php

use App\Models\Blueprint;
use App\Models\User;

test('user can duplicate their own blueprint', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $blueprint = Blueprint::factory()->create([
        'user_id' => $user->id,
        'title' => 'Original',
        'rules' => ['rule-a', 'rule-b'],
    ]);

    $response = $this->withToken($token)
        ->postJson("/api/blueprints/{$blueprint->id}/duplicate");

    $response->assertStatus(201)
        ->assertJson([
            'data' => [
                'title' => 'Original (copy)',
                'rules' => ['rule-a', 'rule-b'],
            ],
        ]);

    expect(Blueprint::count())->toBe(2);
});

test('user cannot duplicate another users blueprint', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $blueprint = Blueprint::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->withToken($token)
        ->postJson("/api/blueprints/{$blueprint->id}/duplicate");

    $response->assertStatus(403);
    expect(Blueprint::count())->toBe(1);
});

test('unauthenticated user cannot duplicate a blueprint', function () {
    $blueprint = Blueprint::factory()->create();

    $response = $this->postJson("/api/blueprints/{$blueprint->id}/duplicate");

    $response->assertStatus(401);
});
