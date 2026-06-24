<?php

use App\Models\Blueprint;
use App\Models\User;

test('blueprint has fillable attributes', function () {
    $blueprint = new Blueprint;
    expect($blueprint->getFillable())->toEqual([
        'user_id', 'title', 'description', 'rules', 'target_audience',
        'tone', 'max_hashtags', 'max_caracteres', 'allow_emojis',
        'forbidden_words', 'regles_supplementaires',
    ]);
});

test('blueprint has casts', function () {
    $blueprint = new Blueprint;
    $casts = $blueprint->getCasts();
    expect($casts['rules'])->toBe('array');
    expect($casts['forbidden_words'])->toBe('array');
    expect($casts['allow_emojis'])->toBe('boolean');
    expect($casts['max_hashtags'])->toBe('integer');
    expect($casts['max_caracteres'])->toBe('integer');
});

test('blueprint belongs to user', function () {
    $user = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['user_id' => $user->id]);

    expect($blueprint->user)->toBeInstanceOf(User::class);
    expect($blueprint->user->id)->toBe($user->id);
});

test('blueprint factory creates valid instance', function () {
    $blueprint = Blueprint::factory()->create();

    expect($blueprint)->toBeInstanceOf(Blueprint::class);
    expect($blueprint->title)->not->toBeEmpty();
    expect($blueprint->user_id)->not->toBeNull();
});

test('blueprint uses HasFactory trait', function () {
    $traits = class_uses_recursive(Blueprint::class);
    expect($traits)->toContain('Illuminate\Database\Eloquent\Factories\HasFactory');
});
