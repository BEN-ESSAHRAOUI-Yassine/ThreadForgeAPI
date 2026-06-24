<?php

use App\Ai\Tools\GetCampaignRules;
use App\Models\Blueprint;
use App\Models\User;
use Laravel\Ai\Tools\Request;

test('get campaign rules returns formatted blueprint data', function () {
    $user = User::factory()->create();
    $blueprint = Blueprint::factory()->create([
        'user_id' => $user->id,
        'title' => 'Tech Style',
        'description' => 'For developers',
        'rules' => ['Keep concise', 'Use code snippets'],
        'target_audience' => 'Developers',
        'tone' => 'Professional',
        'max_hashtags' => 3,
        'max_caracteres' => 280,
        'allow_emojis' => true,
        'forbidden_words' => ['hype', 'fluff'],
        'regles_supplementaires' => 'Always cite sources',
    ]);

    $tool = new GetCampaignRules;
    $request = new Request(['blueprint_id' => $blueprint->id]);
    $result = $tool->handle($request);

    expect($result)->toContain('Title: Tech Style');
    expect($result)->toContain('Description: For developers');
    expect($result)->toContain('Target Audience: Developers');
    expect($result)->toContain('Tone: Professional');
    expect($result)->toContain('Max Hashtags: 3');
    expect($result)->toContain('Max Characters: 280');
    expect($result)->toContain('Allow Emojis: Yes');
    expect($result)->toContain('Forbidden Words: hype, fluff');
    expect($result)->toContain('Additional Rules: Always cite sources');
});

test('get campaign rules returns not found for invalid id', function () {
    $tool = new GetCampaignRules;
    $request = new Request(['blueprint_id' => 99999]);
    $result = $tool->handle($request);

    expect($result)->toBe('Blueprint not found.');
});

test('get campaign rules handles no forbidden words', function () {
    $blueprint = Blueprint::factory()->create([
        'forbidden_words' => null,
    ]);

    $tool = new GetCampaignRules;
    $request = new Request(['blueprint_id' => $blueprint->id]);
    $result = $tool->handle($request);

    expect($result)->toContain('Forbidden Words: None');
});

test('get campaign rules has correct schema structure', function () {
    $mock = Mockery::mock(\Illuminate\Contracts\JsonSchema\JsonSchema::class);
    $mock->shouldReceive('integer')->andReturnSelf();
    $mock->shouldReceive('required')->andReturnSelf();

    $tool = new GetCampaignRules;
    $schema = $tool->schema($mock);

    expect($schema)->toHaveKey('blueprint_id');
});
