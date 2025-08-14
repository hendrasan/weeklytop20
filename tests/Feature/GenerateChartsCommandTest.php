<?php

use App\Jobs\GenerateChart;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;

it('dispatches a GenerateChart job for each user', function (): void {
    Bus::fake();

    User::factory()->count(4)->create();

    Artisan::call('chart:generate');

    Bus::assertDispatchedTimes(GenerateChart::class, 4);
});