<?php

use App\Models\Blueprint;
use App\Models\GeneratedPost;
use App\Models\RawContent;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->blueprint = Blueprint::factory()->create(['user_id' => $this->user->id]);
    $this->otherBlueprint = Blueprint::factory()->create(['user_id' => $this->otherUser->id]);
    $this->token = $this->user->createToken('api-token')->plainTextToken;
});

test('user can list their own generated posts', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
    ]);
    GeneratedPost::factory()->count(3)->create([
        'raw_content_id' => $rawContent->id,
    ]);

    $response = $this->withToken($this->token)->getJson('/api/generated-posts');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'hook_propose', 'body_points', 'technical_readability_score', 'suggested_hashtags', 'statut'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

    expect($response['meta']['total'])->toBe(3);
});

test('user cannot see other users generated posts', function () {
    $otherRawContent = RawContent::factory()->create([
        'user_id' => $this->otherUser->id,
        'blueprint_id' => $this->otherBlueprint->id,
    ]);
    GeneratedPost::factory()->count(2)->create([
        'raw_content_id' => $otherRawContent->id,
    ]);

    $myRawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
    ]);
    GeneratedPost::factory()->create([
        'raw_content_id' => $myRawContent->id,
    ]);

    $response = $this->withToken($this->token)->getJson('/api/generated-posts');

    expect($response['meta']['total'])->toBe(1);
});

test('user can view their own generated post detail', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
    ]);
    $generatedPost = GeneratedPost::factory()->create([
        'raw_content_id' => $rawContent->id,
    ]);

    $response = $this->withToken($this->token)->getJson("/api/generated-posts/{$generatedPost->id}");

    $response->assertStatus(200)
        ->assertJson(['data' => ['id' => $generatedPost->id]]);
});

test('user cannot view another users generated post', function () {
    $otherRawContent = RawContent::factory()->create([
        'user_id' => $this->otherUser->id,
        'blueprint_id' => $this->otherBlueprint->id,
    ]);
    $generatedPost = GeneratedPost::factory()->create([
        'raw_content_id' => $otherRawContent->id,
    ]);

    $response = $this->withToken($this->token)->getJson("/api/generated-posts/{$generatedPost->id}");

    $response->assertStatus(403);
});
