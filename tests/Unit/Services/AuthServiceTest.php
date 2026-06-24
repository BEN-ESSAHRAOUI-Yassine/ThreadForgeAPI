<?php

use App\Models\User;
use App\Services\AuthService;

test('attemptLogin returns user for valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $service = new AuthService;
    $result = $service->attemptLogin([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toBe($user->id);
});

test('attemptLogin returns null for invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $service = new AuthService;
    $result = $service->attemptLogin([
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    expect($result)->toBeNull();
});

test('attemptLogin returns null for non-existent user', function () {
    $service = new AuthService;
    $result = $service->attemptLogin([
        'email' => 'nonexistent@example.com',
        'password' => 'password123',
    ]);

    expect($result)->toBeNull();
});

test('createToken returns a string', function () {
    $user = User::factory()->create();

    $service = new AuthService;
    $token = $service->createToken($user);

    expect($token)->toBeString();
    expect($token)->not->toBeEmpty();
});
