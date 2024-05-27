<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected $commands = [
        Commands\SyncElasticsearchDataProducts::class,
        // Commands\QueryElasticSearch::class,
    ];
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:sync-elasticsearch-data-products')->everyMinute();
        // $schedule->command('app:query-elastic-search')->everyFiveMinutes();

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
