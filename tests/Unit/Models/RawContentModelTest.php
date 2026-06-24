<?php

use App\Enums\RawContentStatus;
use App\Models\Blueprint;
use App\Models\GeneratedPost;
use App\Models\RawContent;
use App\Models\User;

test('raw content has fillable attributes', function () {
    $rawContent = new RawContent;
    expect($rawContent->getFillable())->toEqual([
        'user_id', 'blueprint_id', 'title', 'contenu_brut', 'statut',
    ]);
});

test('raw content has statut cast as RawContentStatus enum', function () {
    $rawContent = new RawContent;
    $casts = $rawContent->getCasts();
    expect($casts['statut'])->toBe('App\Enums\RawContentStatus');
});

test('raw content belongs to user', function () {
    $user = User::factory()->create();
    $rawContent = RawContent::factory()->create(['user_id' => $user->id]);

    expect($rawContent->user)->toBeInstanceOf(User::class);
    expect($rawContent->user->id)->toBe($user->id);
});

test('raw content belongs to blueprint', function () {
    $blueprint = Blueprint::factory()->create();
    $rawContent = RawContent::factory()->create(['blueprint_id' => $blueprint->id]);

    expect($rawContent->blueprint)->toBeInstanceOf(Blueprint::class);
    expect($rawContent->blueprint->id)->toBe($blueprint->id);
});

test('raw content has one generated post', function () {
    $rawContent = RawContent::factory()->create();
    $post = GeneratedPost::factory()->create(['raw_content_id' => $rawContent->id]);

    expect($rawContent->generatedPost)->toBeInstanceOf(GeneratedPost::class);
    expect($rawContent->generatedPost->id)->toBe($post->id);
});

test('raw content defaults to pending status', function () {
    $rawContent = RawContent::factory()->create();

    expect($rawContent->statut)->toBeInstanceOf(RawContentStatus::class);
    expect($rawContent->statut)->toEqual(RawContentStatus::Pending);
});
