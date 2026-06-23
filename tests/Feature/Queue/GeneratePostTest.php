<?php

use App\Enums\RawContentStatus;
use App\Jobs\GeneratePostJob;
use App\Models\Blueprint;
use App\Models\RawContent;
use App\Models\User;
use App\Services\AiGenerationService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->blueprint = Blueprint::factory()->create([
        'user_id' => $this->user->id,
        'tone' => 'professional',
        'target_audience' => 'developers',
        'max_hashtags' => 5,
        'max_caracteres' => 280,
        'allow_emojis' => true,
        'forbidden_words' => ['bad', 'terrible'],
        'rules' => ['style' => 'concise', 'format' => 'thread'],
        'regles_supplementaires' => 'Use code examples where relevant.',
    ]);
    $this->token = $this->user->createToken('api-token')->plainTextToken;
});

test('job is dispatched after submitting raw content', function () {
    Bus::fake();

    $this->withToken($this->token)->postJson('/api/raw-contents', [
        'title' => 'Test',
        'contenu_brut' => 'Content body',
        'blueprint_id' => $this->blueprint->id,
    ]);

    Bus::assertDispatched(GeneratePostJob::class);
});

test('job is not dispatched when validation fails', function () {
    Bus::fake();

    $this->withToken($this->token)->postJson('/api/raw-contents', [
        'contenu_brut' => 'Content',
        'blueprint_id' => $this->blueprint->id,
    ]);

    Bus::assertNotDispatched(GeneratePostJob::class);
});

test('job dispatches with correct raw content id', function () {
    Bus::fake();

    $this->withToken($this->token)->postJson('/api/raw-contents', [
        'title' => 'Test',
        'contenu_brut' => 'Content body',
        'blueprint_id' => $this->blueprint->id,
    ]);

    Bus::assertDispatched(function (GeneratePostJob $job) {
        return $job->rawContentId > 0;
    });
});

test('job updates status to processing and then completed', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
        'statut' => RawContentStatus::Pending,
    ]);

    $generatedData = [
        'hook_propose' => 'Did you know Laravel just got better?',
        'body_points' => ['Point one', 'Point two', 'Point three'],
        'technical_readability_score' => 75,
        'suggested_hashtags' => ['#Laravel', '#PHP', '#WebDev'],
        'tone_compliance_justification' => 'Professional tone maintained throughout.',
    ];

    $this->mock(AiGenerationService::class)
        ->shouldReceive('generate')
        ->once()
        ->andReturn($generatedData);

    $job = new GeneratePostJob($rawContent->id);
    $job->handle(app(AiGenerationService::class));

    $rawContent->refresh();

    expect($rawContent->statut)->toBe(RawContentStatus::Completed);

    $generatedPost = $rawContent->generatedPost;

    expect($generatedPost)->not->toBeNull()
        ->and($generatedPost->hook_propose)->toBe('Did you know Laravel just got better?')
        ->and($generatedPost->body_points)->toBe(['Point one', 'Point two', 'Point three'])
        ->and($generatedPost->technical_readability_score)->toBe(75)
        ->and($generatedPost->suggested_hashtags)->toBe(['#Laravel', '#PHP', '#WebDev'])
        ->and($generatedPost->tone_compliance_justification)->toContain('Professional')
        ->and($generatedPost->statut->value)->toBe('draft');
});

test('job updates status to failed when AI generation throws', function () {
    $rawContent = RawContent::factory()->create([
        'user_id' => $this->user->id,
        'blueprint_id' => $this->blueprint->id,
        'statut' => RawContentStatus::Pending,
    ]);

    $this->mock(AiGenerationService::class)
        ->shouldReceive('generate')
        ->once()
        ->andThrow(new \RuntimeException('AI provider unavailable'));

    $job = new GeneratePostJob($rawContent->id);

    expect(fn () => $job->handle(app(AiGenerationService::class)))
        ->toThrow(\RuntimeException::class, 'AI provider unavailable');

    $rawContent->refresh();

    expect($rawContent->statut)->toBe(RawContentStatus::Failed);
});

test('job has retry configuration', function () {
    $job = new GeneratePostJob(1);

    expect($job->tries)->toBe(3)
        ->and($job->backoff())->toBe([10, 30, 60]);
});

test('unauthenticated user cannot trigger job dispatch', function () {
    Bus::fake();

    $this->postJson('/api/raw-contents', [
        'title' => 'Test',
        'contenu_brut' => 'Content',
        'blueprint_id' => $this->blueprint->id,
    ]);

    Bus::assertNotDispatched(GeneratePostJob::class);
});
