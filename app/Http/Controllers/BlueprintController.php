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
    public function index(): AnonymousResourceCollection
    {
        $blueprints = Blueprint::where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return BlueprintResource::collection($blueprints);
    }

    public function store(BlueprintRequest $request): JsonResponse
    {
        $blueprint = Blueprint::create(
            $request->validated() + ['user_id' => auth()->id()]
        );

        return BlueprintResource::make($blueprint)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Blueprint $blueprint): BlueprintResource
    {
        abort_if($blueprint->user_id !== auth()->id(), 403);

        return new BlueprintResource($blueprint);
    }

    public function update(BlueprintRequest $request, Blueprint $blueprint): BlueprintResource
    {
        abort_if($blueprint->user_id !== auth()->id(), 403);

        $blueprint->update($request->validated());

        return new BlueprintResource($blueprint);
    }

    public function destroy(Blueprint $blueprint): JsonResponse
    {
        abort_if($blueprint->user_id !== auth()->id(), 403);

        $blueprint->delete();

        return response()->json(null, 204);
    }

    public function duplicate(Blueprint $blueprint, BlueprintService $service): JsonResponse
    {
        abort_if($blueprint->user_id !== auth()->id(), 403);

        $clone = $service->duplicate($blueprint, auth()->id());

        return BlueprintResource::make($clone)
            ->response()
            ->setStatusCode(201);
    }
}
