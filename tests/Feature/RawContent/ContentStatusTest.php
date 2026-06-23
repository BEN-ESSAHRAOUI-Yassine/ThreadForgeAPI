<?php

use App\Enums\RawContentStatus;
use App\Models\Blueprint;
use App\Models\RawContent;
use App\Models\User;

test('raw content defaults to pending status', function () {
    $user = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/raw-contents', [
        'title' => 'Test',
        'contenu_brut' => 'Content',
        'blueprint_id' => $blueprint->id,
    ]);

    expect($response['data']['statut'])->toBe('pending');
});

test('raw content status shows in list', function () {
    $user = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('api-token')->plainTextToken;

    RawContent::factory()->create([
        'user_id' => $user->id,
        'blueprint_id' => $blueprint->id,
        'statut' => 'processing',
    ]);

    $response = $this->withToken($token)->getJson('/api/raw-contents');

    expect($response['data'][0]['statut'])->toBe('processing');
});

test('raw content status shows in detail', function () {
    $user = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('api-token')->plainTextToken;

    $rawContent = RawContent::factory()->create([
        'user_id' => $user->id,
        'blueprint_id' => $blueprint->id,
        'statut' => 'completed',
    ]);

    $response = $this->withToken($token)->getJson("/api/raw-contents/{$rawContent->id}");

    expect($response['data']['statut'])->toBe('completed');
});

test('status enum has all expected values', function () {
    expect(RawContentStatus::Pending->value)->toBe('pending');
    expect(RawContentStatus::Processing->value)->toBe('processing');
    expect(RawContentStatus::Completed->value)->toBe('completed');
    expect(RawContentStatus::Failed->value)->toBe('failed');
});
