<?php

namespace App\Http\Controllers;

use App\Enums\RawContentStatus;
use App\Http\Requests\RawContentRequest;
use App\Http\Resources\RawContentResource;
use App\Jobs\GeneratePostJob;
use App\Models\RawContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RawContentController extends Controller
{
    /**
     * List all raw contents
     *
     * Returns a paginated list of raw content submissions belonging to the authenticated user.
     *
     * @queryParam page integer Page number. Example: 1
     *
     * @apiResourceCollection App\Http\Resources\RawContentResource
     * @apiResourceModel App\Models\RawContent paginate=15
     */
    public function index(): AnonymousResourceCollection
    {
        $rawContents = RawContent::where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return RawContentResource::collection($rawContents);
    }

    /**
     * Submit raw content
     *
     * Submits raw content for AI processing. The content is queued for async generation.
     * Returns 202 Accepted immediately — the AI generation happens in the background.
     *
     * @bodyParam title string required A title for the raw content. Example: My Blog Post
     * @bodyParam contenu_brut string required The raw content to process (max 500KB). Example: In this article, we explore how microservices architecture...
     * @bodyParam blueprint_id integer required The ID of the blueprint to use for generation. Must belong to you. Example: 1
     *
     * @apiResource App\Http\Resources\RawContentResource
     * @apiResourceModel App\Models\RawContent
     *
     * @response 202 {
     *   "id": 1,
     *   "user_id": 1,
     *   "blueprint_id": 1,
     *   "title": "My Blog Post",
     *   "contenu_brut": "In this article, we explore how microservices architecture...",
     *   "statut": "pending",
     *   "created_at": "2026-06-23T12:00:00.000000Z",
     *   "updated_at": "2026-06-23T12:00:00.000000Z"
     * }
     * @response 422 {
     *   "message": "The title field is required. (and 2 more errors)",
     *   "errors": {
     *     "title": ["The title field is required."],
     *     "contenu_brut": ["The contenu brut field is required."],
     *     "blueprint_id": ["The selected blueprint does not exist or does not belong to you."]
     *   }
     * }
     */
    public function store(RawContentRequest $request): JsonResponse
    {
        $rawContent = RawContent::create(
            $request->validated() + [
                'user_id' => auth()->id(),
                'statut' => RawContentStatus::Pending,
            ]
        );

        dispatch(new GeneratePostJob($rawContent->id));

        return RawContentResource::make($rawContent)
            ->response()
            ->setStatusCode(202);
    }

    /**
     * Get a single raw content
     *
     * Returns the details of a specific raw content submission. You can only access your own.
     *
     * @urlParam id integer required The raw content ID. Example: 1
     *
     * @apiResource App\Http\Resources\RawContentResource
     * @apiResourceModel App\Models\RawContent
     *
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     */
    public function show(RawContent $rawContent): RawContentResource
    {
        abort_if($rawContent->user_id !== auth()->id(), 403);

        return new RawContentResource($rawContent);
    }

    public function retry(RawContent $rawContent): JsonResponse
    {
        abort_if($rawContent->user_id !== auth()->id(), 403);

        abort_if($rawContent->statut !== RawContentStatus::Failed, 422, 'Only failed posts can be retried.');

        $rawContent->update(['statut' => RawContentStatus::Pending]);

        dispatch(new GeneratePostJob($rawContent->id));

        return RawContentResource::make($rawContent->fresh())
            ->response()
            ->setStatusCode(202);
    }
}
