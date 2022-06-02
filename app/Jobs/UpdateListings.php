<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */

declare(strict_types=1);

namespace App\Jobs;

use App\Services\LandWatchAPIScraper;
use DateTime;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Throwable;

class UpdateListings implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @throws Throwable
     * @throws JsonException
     * @throws ClientExceptionInterface
     */
    public function handle(Dispatcher $dispatcher): void
    {
        $pendingChain = $dispatcher->chain([
            new UpdateStaleEntries(),
            new BatchUpdateAverages(),
        ]);

        $dispatcher
            ->batch([new GetEntryPages(LandWatchAPIScraper::API_URL . '/land')])
            ->name('Update available and under contract listings')
            ->then(static function (Batch $batch) {
                // All jobs completed successfully...
            })->catch(static function (Batch $batch, Throwable $e) {
                // First batch job failure detected...
            })->finally(static function () use ($pendingChain) {
                // The batch has finished executing...
                // We can check stale entries and update county averages now
                $pendingChain->dispatch();
            })->allowFailures()->dispatch();
    }
}
