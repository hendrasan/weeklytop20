<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Spotify;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateChart implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     * @throws \Throwable
     */
    public function handle(Spotify $spotify): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            Log::error('[Jobs\GenerateChart] User not found: ' . $this->userId);
            return;
        }

        try {
            $spotify->generateChart($user);
            Log::info('[Jobs\GenerateChart] Chart generated for user: ' . $user->id);
        } catch (\Throwable $e) {
            // rethrow so the queue can apply retry/failed behavior
            Log::error('[Jobs\GenerateChart] Error for user ' . $user->id . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[Jobs\GenerateChart] Failed job for user ' . $this->userId . ': ' . $exception->getMessage());
    }
}
