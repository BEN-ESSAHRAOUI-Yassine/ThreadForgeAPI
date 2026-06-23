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
    public function index(): AnonymousResourceCollection
    {
        $rawContents = RawContent::where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return RawContentResource::collection($rawContents);
    }

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

    public function show(RawContent $rawContent): RawContentResource
    {
        abort_if($rawContent->user_id !== auth()->id(), 403);

        return new RawContentResource($rawContent);
    }
}
