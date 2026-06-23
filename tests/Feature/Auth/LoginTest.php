<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('users can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
            'token',
        ]);

    expect($response['user']['email'])->toBe('test@example.com');
});

test('users cannot login with wrong password', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401)
        ->assertJson(['message' => 'Invalid credentials']);
});

test('users cannot login with non-existent email', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(401)
        ->assertJson(['message' => 'Invalid credentials']);
});

test('login requires email and password', function () {
    $response = $this->postJson('/api/login', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});
