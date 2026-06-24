<?php

use App\Models\Blueprint;
use App\Models\User;
use App\Services\BlueprintService;

test('duplicate creates a copy with suffix', function () {
    $original = Blueprint::factory()->create([
        'title' => 'Original Blueprint',
    ]);

    $service = new BlueprintService;
    $clone = $service->duplicate($original, $original->user_id);

    expect($clone->id)->not->toBe($original->id);
    expect($clone->title)->toBe('Original Blueprint (copy)');
    expect($clone->user_id)->toBe($original->user_id);
});

test('duplicate replicates all fields', function () {
    $original = Blueprint::factory()->create([
        'description' => 'Test description',
        'rules' => ['rule1', 'rule2'],
        'target_audience' => 'Developers',
        'tone' => 'Professional',
        'max_hashtags' => 5,
        'max_caracteres' => 280,
        'allow_emojis' => false,
        'forbidden_words' => ['badword'],
        'regles_supplementaires' => 'Extra rules',
    ]);

    $service = new BlueprintService;
    $clone = $service->duplicate($original, $original->user_id);

    expect($clone->description)->toBe($original->description);
    expect($clone->rules)->toBe($original->rules);
    expect($clone->target_audience)->toBe($original->target_audience);
    expect($clone->tone)->toBe($original->tone);
    expect($clone->max_hashtags)->toBe($original->max_hashtags);
    expect($clone->max_caracteres)->toBe($original->max_caracteres);
    expect($clone->allow_emojis)->toBe($original->allow_emojis);
    expect($clone->forbidden_words)->toBe($original->forbidden_words);
    expect($clone->regles_supplementaires)->toBe($original->regles_supplementaires);
});

test('duplicate sets new user_id', function () {
    $original = Blueprint::factory()->create();
    $newUser = User::factory()->create();

    $service = new BlueprintService;
    $clone = $service->duplicate($original, $newUser->id);

    expect($clone->user_id)->toBe($newUser->id);
});
