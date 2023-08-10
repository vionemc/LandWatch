<?php

namespace App\Console;

use App\Jobs\UpdateListings;
use App\Jobs\UpdateStaleEntries;
use App\Jobs\UpdateUserAgentsFile;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Queue\Console\FlushFailedCommand;
use Illuminate\Queue\Console\PruneBatchesCommand;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // TODO: Postpone stale entries check after batch listings update
        $schedule->job(new UpdateListings());
        $schedule->job(new UpdateUserAgentsFile())->monthly();

        // Cleanup
        $schedule->command(FlushFailedCommand::class)->daily();
        $schedule->command(PruneBatchesCommand::class, ['--hours' => 96, '--unfinished' => 48]);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
