<?php

use App\Enums\GeneratedPostStatus;

test('generated post status has expected values', function () {
    expect(GeneratedPostStatus::Draft->value)->toBe('draft');
    expect(GeneratedPostStatus::Posted->value)->toBe('posted');
    expect(GeneratedPostStatus::Archived->value)->toBe('archived');
});

test('generated post status has all cases', function () {
    $cases = GeneratedPostStatus::cases();
    expect($cases)->toHaveCount(3);
    expect(array_map(fn ($c) => $c->value, $cases))->toEqual([
        'draft', 'posted', 'archived',
    ]);
});
