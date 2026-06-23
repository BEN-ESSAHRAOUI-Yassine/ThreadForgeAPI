<?php

use App\Models\User;

test('authenticated users can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Logged out successfully']);

    expect($user->tokens()->count())->toBe(0);
});

test('unauthenticated users cannot logout', function () {
    $response = $this->postJson('/api/logout');

    $response->assertStatus(401);
});

test('revoked token cannot logout again', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $user->tokens()->delete();

    $response = $this->withToken($token)
        ->postJson('/api/logout');

    $response->assertStatus(401);
});
