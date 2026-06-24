<?php

use App\Models\Blueprint;
use App\Models\GeneratedPost;
use App\Models\RawContent;
use App\Models\User;

test('user has fillable attributes', function () {
    $user = new User;
    expect($user->getFillable())->toEqual(['name', 'email', 'password']);
});

test('user has hidden attributes', function () {
    $user = new User;
    expect($user->getHidden())->toContain('password', 'remember_token');
});

test('user has password cast as hashed', function () {
    $user = new User;
    $casts = $user->getCasts();
    expect($casts['password'])->toBe('hashed');
});

test('user has blueprints relationship', function () {
    $user = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['user_id' => $user->id]);

    expect($user->blueprints)->toHaveCount(1);
    expect($user->blueprints->first()->id)->toBe($blueprint->id);
});

test('user has generatedPosts relationship through raw contents', function () {
    $user = User::factory()->create();
    $rawContent = RawContent::factory()->create(['user_id' => $user->id]);
    $post = GeneratedPost::factory()->create(['raw_content_id' => $rawContent->id]);

    expect($user->generatedPosts)->toHaveCount(1);
    expect($user->generatedPosts->first()->id)->toBe($post->id);
});

test('user uses HasApiTokens trait', function () {
    $user = new User;
    $traits = class_uses_recursive(User::class);
    expect($traits)->toContain('Laravel\Sanctum\HasApiTokens');
});

test('user uses HasConversations trait', function () {
    $user = new User;
    $traits = class_uses_recursive(User::class);
    expect($traits)->toContain('Laravel\Ai\Concerns\HasConversations');
});
