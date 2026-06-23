<?php

use App\Models\Blueprint;
use App\Models\GeneratedPost;
use App\Models\RawContent;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->blueprint = Blueprint::factory()->create(['user_id' => $this->user->id]);
    $this->otherBlueprint = Blueprint::factory()->create(['user_id' => $this->otherUser->id]);
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
                    'content' => 'Try a bold question as your hook: "Did you know most devs ignore this?"',
                ],
                'finish_reason' => 'stop',
            ],
        ],
        'usage' => ['prompt_tokens' => 50, 'completion_tokens' => 30, 'total_tokens' => 80],
    ])]);
});

test('unauthenticated user cannot chat', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
    ]);
    $post = GeneratedPost::factory()->create(['raw_content_id' => $rawContent->id]);

    $response = $this->postJson("/api/posts/{$post->id}/chat", [
        'message' => 'Give me more hooks',
    ]);

    $response->assertStatus(401);
});

test('user cannot chat on another users post', function () {
    $otherRawContent = RawContent::factory()->create([
        'user_id' => $this->otherUser->id,
        'blueprint_id' => $this->otherBlueprint->id,
    ]);
    $post = GeneratedPost::factory()->create(['raw_content_id' => $otherRawContent->id]);

    $response = $this->withToken($this->token)
        ->postJson("/api/posts/{$post->id}/chat", [
            'message' => 'Give me more hooks',
        ]);

    $response->assertStatus(403);
});
