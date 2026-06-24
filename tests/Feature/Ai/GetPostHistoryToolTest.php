<?php

use App\Ai\Tools\GetPostHistory;
use App\Models\GeneratedPost;
use App\Models\RawContent;
use App\Models\User;
use Laravel\Ai\Tools\Request;

test('get post history returns formatted post data', function () {
    $user = User::factory()->create();
    $rawContent = RawContent::factory()->create(['user_id' => $user->id]);
    $post = GeneratedPost::factory()->create([
        'raw_content_id' => $rawContent->id,
        'hook_propose' => 'Test hook',
        'body_points' => ['Point 1', 'Point 2', 'Point 3'],
        'technical_readability_score' => 75,
        'suggested_hashtags' => ['#laravel', '#php', '#webdev'],
        'tone_compliance_justification' => 'Matches professional tone',
        'statut' => 'draft',
        'posted_at' => null,
    ]);

    $tool = new GetPostHistory;
    $request = new Request(['post_id' => $post->id]);
    $result = $tool->handle($request);

    expect($result)->toContain("Post ID: {$post->id}");
    expect($result)->toContain('Hook: Test hook');
    expect($result)->toContain('1. Point 1');
    expect($result)->toContain('2. Point 2');
    expect($result)->toContain('3. Point 3');
    expect($result)->toContain('Readability Score: 75');
    expect($result)->toContain('#laravel #php #webdev');
    expect($result)->toContain('Tone Justification: Matches professional tone');
    expect($result)->toContain('Status: draft');
    expect($result)->toContain('Posted At: Not posted');
});

test('get post history returns not found for invalid id', function () {
    $tool = new GetPostHistory;
    $request = new Request(['post_id' => 99999]);
    $result = $tool->handle($request);

    expect($result)->toBe('Post not found.');
});

test('get post history shows posted_at when set', function () {
    $user = User::factory()->create();
    $rawContent = RawContent::factory()->create(['user_id' => $user->id]);
    $post = GeneratedPost::factory()->posted()->create([
        'raw_content_id' => $rawContent->id,
    ]);

    $tool = new GetPostHistory;
    $request = new Request(['post_id' => $post->id]);
    $result = $tool->handle($request);

    expect($result)->toContain('Status: posted');
    expect($result)->not->toContain('Not posted');
});

test('get post history has correct schema structure', function () {
    $mock = Mockery::mock(\Illuminate\Contracts\JsonSchema\JsonSchema::class);
    $mock->shouldReceive('integer')->andReturnSelf();
    $mock->shouldReceive('required')->andReturnSelf();

    $tool = new GetPostHistory;
    $schema = $tool->schema($mock);

    expect($schema)->toHaveKey('post_id');
});
