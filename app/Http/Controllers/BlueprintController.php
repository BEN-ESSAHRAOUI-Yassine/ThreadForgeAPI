<?php

namespace App\Http\Controllers;

use App\Http\Requests\BlueprintRequest;
use App\Http\Resources\BlueprintResource;
use App\Models\Blueprint;
use App\Services\BlueprintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlueprintController extends Controller
{
    /**
     * List all blueprints
     *
     * Returns a paginated list of blueprints belonging to the authenticated user.
     *
     * @queryParam page integer Page number. Example: 1
     *
     * @apiResourceCollection App\Http\Resources\BlueprintResource
     * @apiResourceModel App\Models\Blueprint paginate=15
     */
    public function index(): AnonymousResourceCollection
    {
        $blueprints = Blueprint::where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return BlueprintResource::collection($blueprints);
    }

    /**
     * Create a new blueprint
     *
     * Creates a reusable style configuration for AI post generation.
     *
     * @bodyParam title string required A descriptive name for the blueprint. Example: Tech Twitter Style
     * @bodyParam description string A description of what this blueprint is for. Example: For technical deep-dive threads
     * @bodyParam rules array Optional custom rules as a list of strings.
     * @bodyParam rules.* string A single rule. Example: Use technical jargon
     * @bodyParam target_audience string The intended audience. Example: Software engineers
     * @bodyParam tone string The writing tone to use. Example: Professional
     * @bodyParam max_hashtags integer Maximum number of hashtags (0-50). Example: 5
     * @bodyParam max_caracteres integer Maximum character count per post (1-10000). Example: 280
     * @bodyParam allow_emojis boolean Whether emojis are allowed. Example: true
     * @bodyParam forbidden_words array List of forbidden words.
     * @bodyParam forbidden_words.* string A forbidden word. Example: synergy
     * @bodyParam regles_supplementaires string Additional rules in free text. Example: Always include a call to action
     *
     * @apiResource App\Http\Resources\BlueprintResource
     * @apiResourceModel App\Models\Blueprint
     *
     * @response 201 {
     *   "id": 1,
     *   "user_id": 1,
     *   "title": "Tech Twitter Style",
     *   "description": "For technical deep-dive threads",
     *   "rules": ["Use technical jargon"],
     *   "target_audience": "Software engineers",
     *   "tone": "Professional",
     *   "max_hashtags": 5,
     *   "max_caracteres": 280,
     *   "allow_emojis": true,
     *   "forbidden_words": ["synergy"],
     *   "regles_supplementaires": "Always include a call to action",
     *   "created_at": "2026-06-23T12:00:00.000000Z",
     *   "updated_at": "2026-06-23T12:00:00.000000Z"
     * }
     */
    public function store(BlueprintRequest $request): JsonResponse
    {
        $blueprint = Blueprint::create(
            $request->validated() + ['user_id' => auth()->id()]
        );

        return BlueprintResource::make($blueprint)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a single blueprint
     *
     * Returns the details of a specific blueprint. You can only access your own blueprints.
     *
     * @urlParam id integer required The blueprint ID. Example: 1
     *
     * @apiResource App\Http\Resources\BlueprintResource
     * @apiResourceModel App\Models\Blueprint
     *
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     */
    public function show(Blueprint $blueprint): BlueprintResource
    {
        abort_if($blueprint->user_id !== auth()->id(), 403);

        return new BlueprintResource($blueprint);
    }

    /**
     * Update a blueprint
     *
     * Updates an existing blueprint. You can only update your own blueprints.
     *
     * @urlParam id integer required The blueprint ID. Example: 1
     *
     * @bodyParam title string A descriptive name for the blueprint. Example: Updated Tech Style
     * @bodyParam description string A description of what this blueprint is for. Example: Updated description
     * @bodyParam rules array Optional custom rules as a list of strings.
     * @bodyParam rules.* string A single rule. Example: Keep it concise
     * @bodyParam target_audience string The intended audience. Example: Developers
     * @bodyParam tone string The writing tone to use. Example: Casual
     * @bodyParam max_hashtags integer Maximum number of hashtags (0-50). Example: 3
     * @bodyParam max_caracteres integer Maximum character count per post (1-10000). Example: 240
     * @bodyParam allow_emojis boolean Whether emojis are allowed. Example: false
     * @bodyParam forbidden_words array List of forbidden words.
     * @bodyParam forbidden_words.* string A forbidden word. Example: buzzword
     * @bodyParam regles_supplementaires string Additional rules in free text. Example: No marketing speak
     *
     * @apiResource App\Http\Resources\BlueprintResource
     * @apiResourceModel App\Models\Blueprint
     *
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     */
    public function update(BlueprintRequest $request, Blueprint $blueprint): BlueprintResource
    {
        abort_if($blueprint->user_id !== auth()->id(), 403);

        $blueprint->update($request->validated());

        return new BlueprintResource($blueprint);
    }

    /**
     * Delete a blueprint
     *
     * Deletes a blueprint. You can only delete your own blueprints.
     *
     * @urlParam id integer required The blueprint ID. Example: 1
     *
     * @response 204 No content
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     */
    public function destroy(Blueprint $blueprint): JsonResponse
    {
        abort_if($blueprint->user_id !== auth()->id(), 403);

        $blueprint->delete();

        return response()->json(null, 204);
    }

    /**
     * Duplicate a blueprint
     *
     * Creates a copy of an existing blueprint, including all its settings. You can only duplicate your own blueprints.
     *
     * @urlParam id integer required The blueprint ID to duplicate. Example: 1
     *
     * @apiResource App\Http\Resources\BlueprintResource
     * @apiResourceModel App\Models\Blueprint
     *
     * @response 201 {
     *   "id": 2,
     *   "user_id": 1,
     *   "title": "Tech Twitter Style (Copy)",
     *   ...same structure as a blueprint...
     * }
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     */
    public function duplicate(Blueprint $blueprint, BlueprintService $service): JsonResponse
    {
        abort_if($blueprint->user_id !== auth()->id(), 403);

        $clone = $service->duplicate($blueprint, auth()->id());

        return BlueprintResource::make($clone)
            ->response()
            ->setStatusCode(201);
    }
}
