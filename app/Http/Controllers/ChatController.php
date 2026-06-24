<?php

namespace App\Http\Controllers;

use App\Ai\Agents\PostChatAgent;
use App\Http\Requests\ChatRequest;
use App\Models\GeneratedPost;
use Illuminate\Http\JsonResponse;
use Laravel\Ai\Models\Conversation;

class ChatController extends Controller
{
    /**
     * Chat with the AI assistant about a generated post
     *
     * Sends a message to the AI assistant for a specific generated post.
     * The assistant has access to the blueprint rules (via GetCampaignRules tool)
     * and the post history (via GetPostHistory tool) to provide context-aware responses.
     * Conversations are scoped per user per post for continuity.
     *
     * @urlParam post integer required The generated post ID to chat about. Example: 1
     *
     * @bodyParam message string required The message to send to the AI assistant (max 5000 characters). Example: Make the hook more aggressive
     *
     * @response {
     *   "data": {
     *     "response": "Here's a more aggressive hook: 'Microservices are NOT dead — and here's why most teams are doing them wrong.'",
     *     "conversation_id": "abc-123-def"
     *   }
     * }
     * @response 422 {
     *   "message": "The message field is required.",
     *   "errors": {
     *     "message": ["The message field is required."]
     *   }
     * }
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\GeneratedPost]."
     * }
     */
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
