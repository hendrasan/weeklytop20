<?php

it('annotates runs correctly with period objects and gaps', function () {
    $runs = [
        ['period' => 131, 'position' => 15],
        ['period' => 132, 'position' => 4],
        ['period' => 133, 'position' => 3],
        ['period' => 134, 'position' => 5],
        ['period' => 135, 'position' => 7],
        ['period' => 136, 'position' => 4],
    ];
    $currentPeriod = 134;

    $annotated = annotateRuns($runs, $currentPeriod);

    expect(count($annotated))->toBe(6);

    // First position
    expect($annotated[0]['display'])->toBe('15');
    expect($annotated[0]['trend'])->toBeNull();
    expect($annotated[0]['is_current'])->toBeFalse();

    // Peak position (3 is lowest)
    expect($annotated[2]['display'])->toBe('3');
    expect($annotated[2]['is_peak'])->toBeTrue();

    // Current period
    expect($annotated[3]['display'])->toBe('5');
    expect($annotated[3]['is_current'])->toBeTrue();
    expect($annotated[3]['period'])->toBe(134);

    // Trend: 15 -> 4 is 'up' (position improved)
    expect($annotated[1]['trend'])->toBe('up');

    // Trend: 4 -> 3 is 'up' (position improved)
    expect($annotated[2]['trend'])->toBe('up');

    // Trend: 3 -> 5 is 'down' (position worsened)
    expect($annotated[3]['trend'])->toBe('down');
});

it('handles missing positions correctly', function () {
    $runs = [
        ['period' => 131, 'position' => 10],
        ['period' => 132, 'position' => 0],
        ['period' => 133, 'position' => 5],
    ];
    $currentPeriod = 132;

    $annotated = annotateRuns($runs, $currentPeriod);

    expect($annotated[1]['display'])->toBe('—');
    expect($annotated[1]['is_current'])->toBeTrue();
    expect($annotated[2]['trend'])->toBeNull(); // No trend from 0 position
});

it('adds gap indicators for non-consecutive periods', function () {
    $runs = [
        ['period' => 131, 'position' => 10],
        ['period' => 133, 'position' => 5], // Gap at period 132
        ['period' => 134, 'position' => 3],
    ];
    $currentPeriod = 134;

    $annotated = annotateRuns($runs, $currentPeriod);
    expect(count($annotated))->toBe(4); // Original 3 + 1 gap

    // First position
    expect($annotated[0]['display'])->toBe('10');
    expect($annotated[0])->not->toHaveKey('is_gap');

    // Gap indicator
    expect($annotated[1]['display'])->toBe('...');
    expect($annotated[1]['is_gap'])->toBeTrue();
    expect($annotated[1]['trend'])->toBeNull();

    // Position after gap
    expect($annotated[2]['display'])->toBe('5');
    expect($annotated[2])->not->toHaveKey('is_gap');
});