<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Listing;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class BatchUpdateAverages implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->onQueue('averages');
    }

    /**
     * Execute the job.
     * @throws Throwable
     */
    public function handle(Dispatcher $dispatcher, Connection $db): void
    {
        $count = $db->table('listings')
            ->whereIn('status', [Listing::STATUS_AVAILABLE, Listing::STATUS_UNDER_CONTRACT])
            ->count('id');

        if ($count > 0) {
            // Remove old avg values before starting
            $db->table('listings')
                ->whereIn('status', [Listing::STATUS_AVAILABLE, Listing::STATUS_UNDER_CONTRACT])
                ->update([
                    'local_avg_price_per_acre' => NULL,
                    'local_min_price_per_acre' => NULL,
                    'local_median_price_per_acre' => NULL,
                ]);

            $dispatcher->batch([new UpdateAverages()])
                ->name('Update active listings averages.')
                ->then(static function (Batch $batch) {
                    // All jobs completed successfully...
                })->catch(static function (Batch $batch, Throwable $e) {
                    // First batch job failure detected...
                })->finally(static function () {
                    // The batch has finished executing...
                    // We can check subscriptions
                     CheckSubscriptions::dispatch();
                })->allowFailures()
                ->onQueue('averages')
                ->dispatch();
        }
    }
}
