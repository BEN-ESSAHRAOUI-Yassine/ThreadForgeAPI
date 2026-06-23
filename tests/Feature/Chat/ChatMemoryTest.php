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

    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
    ]);
    $this->post = GeneratedPost::factory()->create(['raw_content_id' => $rawContent->id]);

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
                    'content' => 'AI response placeholder.',
                ],
                'finish_reason' => 'stop',
            ],
        ],
        'usage' => ['prompt_tokens' => 50, 'completion_tokens' => 30, 'total_tokens' => 80],
    ])]);
});

test('second message in same conversation returns same conversation_id', function () {
    $first = $this->withToken($this->token)
        ->postJson("/api/posts/{$this->post->id}/chat", [
            'message' => 'Give me more hooks',
        ]);

    $conversationId = $first['data']['conversation_id'];

    $second = $this->withToken($this->token)
        ->postJson("/api/posts/{$this->post->id}/chat", [
            'message' => 'Make them shorter',
        ]);

    expect($second['data']['conversation_id'])->toBe($conversationId);
});

test('different generated post creates different conversation', function () {
    $blueprint2 = Blueprint::factory()->create(['user_id' => $this->user->id]);
    $rawContent2 = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $blueprint2->id,
    ]);
    $post2 = GeneratedPost::factory()->create(['raw_content_id' => $rawContent2->id]);

    $first = $this->withToken($this->token)
        ->postJson("/api/posts/{$this->post->id}/chat", [
            'message' => 'Help me refine this',
        ]);

    $second = $this->withToken($this->token)
        ->postJson("/api/posts/{$post2->id}/chat", [
            'message' => 'Help me refine this',
        ]);

    expect($first['data']['conversation_id'])->not->toBe($second['data']['conversation_id']);
});
