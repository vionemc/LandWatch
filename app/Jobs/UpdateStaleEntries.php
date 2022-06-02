<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Listing;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Throwable;

use function array_map;

final class UpdateStaleEntries implements ShouldQueue
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
        //
    }

    /**
     * Execute the job.
     * @throws Throwable
     */
    public function handle(Dispatcher $dispatcher): void
    {
        $batch = $dispatcher
            ->batch([])
            ->name('Check and update stale listings.')
            ->then(static function (Batch $batch) {
                // All jobs completed successfully...
            })->catch(static function (Batch $batch, Throwable $e) {
                // First batch job failure detected...
            })->finally(static function () {

            })->allowFailures()->dispatch();

        Listing::select(['id'])
            ->where(new Expression('DATE(`checked_at`)'), '<', new Expression('CURDATE() - INTERVAL 3 DAY'))
            ->where(static function (Builder $query) {
                $query->where(['status' => Listing::STATUS_AVAILABLE])->orWhere(['status' => Listing::STATUS_UNDER_CONTRACT]);
            })
            ->orderBy('id')
            ->getQuery()->chunk(1000, static function (Collection $staleListings) use ($batch) {
                $jobs = $staleListings->chunk(25)->map(static function (Collection $chunk) {
                    return new CheckStaleEntries($chunk->pluck('id')->all());
                });
                $batch->add($jobs->all());
            });
    }
}
