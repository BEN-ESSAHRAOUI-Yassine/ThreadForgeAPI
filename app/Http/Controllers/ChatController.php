<?php

namespace App\Http\Controllers;

use App\Ai\Agents\PostChatAgent;
use App\Http\Requests\ChatRequest;
use App\Models\GeneratedPost;
use Illuminate\Http\JsonResponse;
use Laravel\Ai\Models\Conversation;

class ChatController extends Controller
{
    public function __invoke(GeneratedPost $post, ChatRequest $request): JsonResponse
    {
        abort_if($post->rawContent->user_id !== auth()->id(), 403);

        $user = auth()->user();

        $conversation = Conversation::firstOrCreate(
            ['user_id' => $user->id, 'generated_post_id' => $post->id],
            ['id' => str()->uuid(), 'title' => "Chat about post #{$post->id}"],
        );

        $agent = new PostChatAgent;

        $response = $agent->continue($conversation->id, as: $user)->prompt($request->message);

        return response()->json([
            'data' => [
                'response' => (string) $response,
                'conversation_id' => $response->conversationId,
            ],
        ]);
    }
}
