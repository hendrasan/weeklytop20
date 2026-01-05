<?php

namespace App\Console\Commands;

use App\Jobs\GenerateChart;
use Log;
use App\Models\User;
use Illuminate\Console\Command;

use App\Services\Spotify;

class GenerateCharts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chart:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate charts for all users who want to have updated charts';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        protected Spotify $spotify
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Dispatching chart generation jobs...');

        $total = User::count();
        $bar = $this->output->createProgressBar($total);

        $bar->start();

        User::cursor()->each(function (User $user) use ($bar) {
            // Dispatch the job for each user
            // GenerateChart::dispatch($user->id);
            $this->spotify->generateChart($user);
            $bar->advance();
        });

        $bar->finish();

        $this->info(PHP_EOL . 'All charts generation jobs dispatched successfully!');
        Log::info('[Commands\GenerateCharts] All charts generation jobs dispatched successfully!');
    }
}
