<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Chart;
use App\Services\ChartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChartServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_user_chart_with_aggregates()
    {
        $user = User::factory()->create();
        // Create chart data for the user
        Chart::factory()->count(3)->create([
            'user_id' => $user->id,
            'created_at' => now(),
            'track_spotify_id' => 'track1',
            'position' => 1,
            'periods_on_chart' => 1,
        ]);

        $service = new ChartService();
        $result = $service->getUserChartWithAggregates($user, now()->year);

        $this->assertNotEmpty($result);
        $this->assertEquals('track1', $result->first()->track_spotify_id);
        $this->assertArrayHasKey('score', $result->first()->getAttributes());
    }
}
