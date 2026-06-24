<?php

use App\Models\GeneratedPost;
use App\Models\RawContent;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('api-token')->plainTextToken;
});

test('message exceeding 5000 characters is rejected', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
    ]);
    $post = GeneratedPost::factory()->create(['raw_content_id' => $rawContent->id]);

    $response = $this->withToken($this->token)
        ->postJson("/api/posts/{$post->id}/chat", [
            'message' => str_repeat('a', 5001),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['message']);
});

test('message at exactly 5000 characters is accepted', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
    ]);
    $post = GeneratedPost::factory()->create(['raw_content_id' => $rawContent->id]);

    $response = $this->withToken($this->token)
        ->postJson("/api/posts/{$post->id}/chat", [
            'message' => str_repeat('b', 5000),
        ]);

    expect($response->status())->toBeIn([200, 422]);
    if ($response->status() === 422) {
        $response->assertJsonMissingValidationErrors(['message']);
    }
});
