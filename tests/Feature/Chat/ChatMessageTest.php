<?php

use App\Models\Blueprint;
use App\Models\GeneratedPost;
use App\Models\RawContent;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->blueprint = Blueprint::factory()->create(['user_id' => $this->user->id]);
    $this->token = $this->user->createToken('api-token')->plainTextToken;

    Http::fake(['api.groq.com/*' => Http::response([
        'id' => 'chatcmpl-test',
        'object' => 'chat.completion',
        'created' => now()->timestamp,
        'model' => 'meta-llama/llama-4-scout-17b-16e-instruct',
        'choices' => [
            [
                'index' => 0,
                'message' => [
                    'role' => 'assistant',
                    'content' => 'Try using a surprising statistic as your opening hook.',
                ],
                'finish_reason' => 'stop',
            ],
        ],
        'usage' => ['prompt_tokens' => 50, 'completion_tokens' => 30, 'total_tokens' => 80],
    ])]);
});

test('user can send a chat message and get response', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
    ]);
    $post = GeneratedPost::factory()->create(['raw_content_id' => $rawContent->id]);

    $response = $this->withToken($this->token)
        ->postJson("/api/posts/{$post->id}/chat", [
            'message' => 'Give me more hooks',
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['response', 'conversation_id'],
        ]);

    expect($response['data']['response'])->toBeString();
    expect($response['data']['conversation_id'])->toBeString();
});

test('message is required', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
    ]);
    $post = GeneratedPost::factory()->create(['raw_content_id' => $rawContent->id]);

    $response = $this->withToken($this->token)
        ->postJson("/api/posts/{$post->id}/chat", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['message']);
});

test('message must be a string', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
    ]);
    $post = GeneratedPost::factory()->create(['raw_content_id' => $rawContent->id]);

    $response = $this->withToken($this->token)
        ->postJson("/api/posts/{$post->id}/chat", [
            'message' => 123,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['message']);
});

test('non-existent post returns 404', function () {
    $response = $this->withToken($this->token)
        ->postJson('/api/posts/99999/chat', [
            'message' => 'Hello',
        ]);

    $response->assertStatus(404);
});
