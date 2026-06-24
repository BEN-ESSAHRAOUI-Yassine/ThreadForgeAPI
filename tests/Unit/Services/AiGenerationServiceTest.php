<?php

use App\Models\Blueprint;
use App\Models\RawContent;
use App\Services\AiGenerationService;

test('buildPrompt constructs prompt with all fields', function () {
    $blueprint = Blueprint::factory()->create([
        'rules' => ['rule1' => 'value1', 'rule2' => 'value2'],
        'target_audience' => 'Developers',
        'tone' => 'Professional',
        'max_hashtags' => 5,
        'max_caracteres' => 280,
        'allow_emojis' => true,
        'forbidden_words' => ['badword1', 'badword2'],
        'regles_supplementaires' => 'Always cite sources.',
    ]);
    $rawContent = RawContent::factory()->create([
        'blueprint_id' => $blueprint->id,
        'title' => 'Test Title',
        'contenu_brut' => 'Test content body.',
    ]);

    $service = new AiGenerationService;
    $reflection = new ReflectionMethod($service, 'buildPrompt');
    $prompt = $reflection->invoke($service, $rawContent, $blueprint);

    expect($prompt)->toContain('Test Title');
    expect($prompt)->toContain('Test content body.');
    expect($prompt)->toContain('rule1: value1');
    expect($prompt)->toContain('rule2: value2');
    expect($prompt)->toContain('Target Audience: Developers');
    expect($prompt)->toContain('Tone: Professional');
    expect($prompt)->toContain('Max Hashtags: 5');
    expect($prompt)->toContain('Max Characters Per Post: 280');
    expect($prompt)->toContain('Allow Emojis: Yes');
    expect($prompt)->toContain('badword1');
    expect($prompt)->toContain('badword2');
    expect($prompt)->toContain('Additional Rules: Always cite sources.');
});

test('buildPrompt handles null optional fields', function () {
    $blueprint = Blueprint::factory()->create([
        'rules' => null,
        'target_audience' => null,
        'tone' => null,
        'max_hashtags' => null,
        'max_caracteres' => null,
        'allow_emojis' => true,
        'forbidden_words' => null,
        'regles_supplementaires' => null,
    ]);
    $rawContent = RawContent::factory()->create([
        'blueprint_id' => $blueprint->id,
        'title' => 'Minimal',
        'contenu_brut' => 'Some content.',
    ]);

    $service = new AiGenerationService;
    $reflection = new ReflectionMethod($service, 'buildPrompt');
    $prompt = $reflection->invoke($service, $rawContent, $blueprint);

    expect($prompt)->toContain('Minimal');
    expect($prompt)->toContain('Allow Emojis: Yes');
    expect($prompt)->toContain('None');
});
