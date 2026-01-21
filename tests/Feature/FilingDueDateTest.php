<?php

declare(strict_types=1);

use App\Models\Filing;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores a due date on filings', function () {
    $filing = Filing::factory()->create([
        'due_date' => '2026-04-15',
    ]);

    expect($filing->fresh()->due_date?->toDateString())->toBe('2026-04-15');
});
