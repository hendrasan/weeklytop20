<?php

namespace App\Console\Commands;

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
    public function handle()
    {
        $this->info('Starting chart generation...');

        // TODO: change this to only get users who want to have updated charts,
        //       maybe by adding a flag column to the users table
        $users = User::all();

        $bar = $this->output->createProgressBar(count($users));

        foreach ($users as $user) {
            // TODO: change this using queues
            try {
                $this->spotify->generateChart($user);
                $this->info('Chart for ' . $user->name . ' generated successfully!');
            } catch (\Exception $e) {
                $this->error('Error generating chart for ' . $user->name . ': ' . $e->getMessage());
                Log::error('[Commands\GenerateCharts] Error generating chart for ' . $user->name . ': ' . $e->getMessage());
                continue;
            }

            $bar->advance();
        }

        $bar->finish();

        $this->info(PHP_EOL . 'All charts generated successfully!');
        Log::info('[Commands\GenerateCharts] All charts generated successfully!');
    }
}
