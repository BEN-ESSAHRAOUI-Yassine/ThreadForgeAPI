<?php

use App\Models\User;

test('unauthenticated requests return 401 on protected routes', function () {
    $response = $this->getJson('/api/user');

    $response->assertStatus(401);
});

test('authenticated requests can access protected routes', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/user');

    $response->assertStatus(200)
        ->assertJson([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
});

test('protected routes are inaccessible with invalid token', function () {
    $response = $this->withToken('invalid-token')
        ->getJson('/api/user');

    $response->assertStatus(401);
});

test('register route is publicly accessible', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(201);
});

test('login route is publicly accessible', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(401);
});
