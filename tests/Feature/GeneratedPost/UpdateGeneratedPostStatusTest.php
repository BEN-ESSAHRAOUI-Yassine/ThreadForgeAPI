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

test('user can update status from draft to posted', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
    ]);
    $generatedPost = GeneratedPost::factory()->create([
        'raw_content_id' => $rawContent->id,
        'statut' => 'draft',
    ]);

    expect($generatedPost->posted_at)->toBeNull();

    $response = $this->withToken($this->token)
        ->patchJson("/api/generated-posts/{$generatedPost->id}/status", [
            'statut' => 'posted',
        ]);

    $response->assertStatus(200);
    expect($response['data']['statut'])->toBe('posted');
    expect($response['data']['posted_at'])->not->toBeNull();
});

test('user can update status from posted to archived', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
    ]);
    $generatedPost = GeneratedPost::factory()->create([
        'raw_content_id' => $rawContent->id,
        'statut' => 'posted',
        'posted_at' => now(),
    ]);

    $response = $this->withToken($this->token)
        ->patchJson("/api/generated-posts/{$generatedPost->id}/status", [
            'statut' => 'archived',
        ]);

    $response->assertStatus(200);
    expect($response['data']['statut'])->toBe('archived');
});

test('posted_at is cleared when moving from posted to draft', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
    ]);
    $generatedPost = GeneratedPost::factory()->create([
        'raw_content_id' => $rawContent->id,
        'statut' => 'posted',
        'posted_at' => now(),
    ]);

    $response = $this->withToken($this->token)
        ->patchJson("/api/generated-posts/{$generatedPost->id}/status", [
            'statut' => 'draft',
        ]);

    $response->assertStatus(200);
    expect($response['data']['statut'])->toBe('draft');
    expect($response['data']['posted_at'])->toBeNull();
});

test('invalid status returns validation error', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
    ]);
    $generatedPost = GeneratedPost::factory()->create([
        'raw_content_id' => $rawContent->id,
    ]);

    $response = $this->withToken($this->token)
        ->patchJson("/api/generated-posts/{$generatedPost->id}/status", [
            'statut' => 'invalid_status',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['statut']);
});

test('user cannot update another users generated post status', function () {
    $otherRawContent = RawContent::factory()->create([
        'user_id' => $this->otherUser->id,
        'blueprint_id' => $this->otherBlueprint->id,
    ]);
    $generatedPost = GeneratedPost::factory()->create([
        'raw_content_id' => $otherRawContent->id,
    ]);

    $response = $this->withToken($this->token)
        ->patchJson("/api/generated-posts/{$generatedPost->id}/status", [
            'statut' => 'archived',
        ]);

    $response->assertStatus(403);
});
