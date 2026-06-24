<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGeneratedPostStatusRequest;
use App\Http\Resources\GeneratedPostResource;
use App\Models\GeneratedPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GeneratedPostController extends Controller
{
    /**
     * List all generated posts
     *
     * Returns a paginated list of generated posts belonging to the authenticated user.
     *
     * @queryParam page integer Page number. Example: 1
     *
     * @apiResourceCollection App\Http\Resources\GeneratedPostResource
     * @apiResourceModel App\Models\GeneratedPost paginate=15
     */
    public function index(): AnonymousResourceCollection
    {
        $generatedPosts = GeneratedPost::whereHas('rawContent', function ($query) {
            $query->where('user_id', auth()->id());
        })
            ->latest()
            ->paginate(15);

        return GeneratedPostResource::collection($generatedPosts);
    }

    /**
     * Get a single generated post
     *
     * Returns the details of a specific generated post. You can only access your own posts.
     *
     * @urlParam id integer required The generated post ID. Example: 1
     *
     * @apiResource App\Http\Resources\GeneratedPostResource
     * @apiResourceModel App\Models\GeneratedPost
     *
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     */
    public function show(GeneratedPost $generatedPost): GeneratedPostResource
    {
        abort_if($generatedPost->rawContent->user_id !== auth()->id(), 403);

        return new GeneratedPostResource($generatedPost);
    }

    /**
     * Update generated post status
     *
     * Updates the publication status of a generated post.
     * - Moving to "posted" automatically sets the `posted_at` timestamp.
     * - Moving from "posted" to another status clears `posted_at`.
     *
     * @urlParam id integer required The generated post ID. Example: 1
     *
     * @bodyParam statut string required The new status. Must be one of: draft, posted, archived. Example: posted
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "raw_content_id": 1,
     *     "hook_propose": "Microservices aren't dead—they're evolving",
     *     "body_points": ["Point 1", "Point 2"],
     *     "technical_readability_score": 75,
     *     "suggested_hashtags": ["#microservices", "#architecture"],
     *     "tone_compliance_justification": "Matches professional tone",
     *     "statut": "posted",
     *     "posted_at": "2026-06-23T12:00:00.000000Z",
     *     "created_at": "2026-06-23T12:00:00.000000Z",
     *     "updated_at": "2026-06-23T12:00:00.000000Z"
     *   }
     * }
     * @response 422 {
     *   "message": "The statut must be one of: draft, posted, archived.",
     *   "errors": {
     *     "statut": ["The statut must be one of: draft, posted, archived."]
     *   }
     * }
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     */
    public function update(UpdateGeneratedPostStatusRequest $request, GeneratedPost $generatedPost): JsonResponse
    {
        abort_if($generatedPost->rawContent->user_id !== auth()->id(), 403);

        $newStatus = $request->validated()['statut'];

        $data = ['statut' => $newStatus];

        if ($newStatus === 'posted') {
            $data['posted_at'] = now();
        } elseif ($generatedPost->statut?->value === 'posted' && $newStatus !== 'posted') {
            $data['posted_at'] = null;
        }

        $generatedPost->update($data);

        return response()->json([
            'data' => new GeneratedPostResource($generatedPost->fresh()),
        ]);
    }
}
