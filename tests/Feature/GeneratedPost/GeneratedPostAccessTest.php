<?php

use App\Models\Blueprint;
use App\Models\GeneratedPost;
use App\Models\RawContent;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->blueprint = Blueprint::factory()->create(['user_id' => $this->user->id]);
});

test('unauthenticated user cannot list generated posts', function () {
    $response = $this->getJson('/api/generated-posts');

    $response->assertStatus(401);
});

test('unauthenticated user cannot view generated post detail', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
    ]);
    $generatedPost = GeneratedPost::factory()->create([
        'raw_content_id' => $rawContent->id,
    ]);

    $response = $this->getJson("/api/generated-posts/{$generatedPost->id}");

    $response->assertStatus(401);
});

test('unauthenticated user cannot update generated post status', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
    ]);
    $generatedPost = GeneratedPost::factory()->create([
        'raw_content_id' => $rawContent->id,
    ]);

    $response = $this->patchJson("/api/generated-posts/{$generatedPost->id}/status", [
        'statut' => 'posted',
    ]);

    $response->assertStatus(401);
});
