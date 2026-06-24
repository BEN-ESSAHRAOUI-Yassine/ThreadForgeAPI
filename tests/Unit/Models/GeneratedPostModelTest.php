<?php

use App\Enums\GeneratedPostStatus;
use App\Models\GeneratedPost;
use App\Models\RawContent;

test('generated post has fillable attributes', function () {
    $post = new GeneratedPost;
    expect($post->getFillable())->toEqual([
        'raw_content_id', 'hook_propose', 'body_points',
        'technical_readability_score', 'suggested_hashtags',
        'tone_compliance_justification', 'payload_brut',
        'statut', 'posted_at',
    ]);
});

test('generated post has casts', function () {
    $post = new GeneratedPost;
    $casts = $post->getCasts();
    expect($casts['body_points'])->toBe('array');
    expect($casts['suggested_hashtags'])->toBe('array');
    expect($casts['payload_brut'])->toBe('array');
    expect($casts['statut'])->toBe('App\Enums\GeneratedPostStatus');
    expect($casts['technical_readability_score'])->toBe('integer');
    expect($casts['posted_at'])->toBe('datetime');
});

test('generated post belongs to raw content', function () {
    $rawContent = RawContent::factory()->create();
    $post = GeneratedPost::factory()->create(['raw_content_id' => $rawContent->id]);

    expect($post->rawContent)->toBeInstanceOf(RawContent::class);
    expect($post->rawContent->id)->toBe($rawContent->id);
});

test('generated post defaults to draft status', function () {
    $post = GeneratedPost::factory()->create();

    expect($post->statut)->toBeInstanceOf(GeneratedPostStatus::class);
    expect($post->statut)->toEqual(GeneratedPostStatus::Draft);
});

test('generated post factory creates valid instance with array fields', function () {
    $post = GeneratedPost::factory()->create();

    expect($post->hook_propose)->not->toBeEmpty();
    expect($post->body_points)->toBeArray();
    expect($post->suggested_hashtags)->toBeArray();
    expect($post->payload_brut)->toBeArray();
    expect($post->technical_readability_score)->toBeInt();
});

test('generated post factory posted state sets status and posted_at', function () {
    $post = GeneratedPost::factory()->posted()->create();

    expect($post->statut)->toEqual(GeneratedPostStatus::Posted);
    expect($post->posted_at)->not->toBeNull();
});

test('generated post factory archived state sets status', function () {
    $post = GeneratedPost::factory()->archived()->create();

    expect($post->statut)->toEqual(GeneratedPostStatus::Archived);
});
