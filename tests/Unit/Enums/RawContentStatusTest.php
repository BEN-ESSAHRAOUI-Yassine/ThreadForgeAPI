<?php

use App\Enums\RawContentStatus;

test('raw content status has expected values', function () {
    expect(RawContentStatus::Pending->value)->toBe('pending');
    expect(RawContentStatus::Processing->value)->toBe('processing');
    expect(RawContentStatus::Completed->value)->toBe('completed');
    expect(RawContentStatus::Failed->value)->toBe('failed');
});

test('raw content status has all cases', function () {
    $cases = RawContentStatus::cases();
    expect($cases)->toHaveCount(4);
    expect(array_map(fn ($c) => $c->value, $cases))->toEqual([
        'pending', 'processing', 'completed', 'failed',
    ]);
});
