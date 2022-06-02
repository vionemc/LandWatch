<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Listing;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;

class UpdateAverages implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use Batchable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private int $startingId = 0,
        private int $limit = 1000,
        private float $area_min_ratio = 0.5,
        private float $area_max_ratio = 1.5,
        private int $minimum_number_of_locals = 8,
        private bool $recheck = false,
    ) {
        $this->onQueue('averages');
    }

    /**
     * Execute the job.
     */
    public function handle(Connection $db, LoggerInterface $logger): void
    {
        if ($this->batch() !== null && $this->batch()->canceled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $cte = $db->table('listings', 'j1')
            ->select(['id', 'price', 'area', 'price_per_acre', 'county', 'state'])
            ->whereIn('status', [Listing::STATUS_AVAILABLE, Listing::STATUS_UNDER_CONTRACT])
            ->orderBy('price_per_acre');

        $t1 = $db->table('listings', 't1')
            ->select([
                't1.id',
                't1.area',
                't1.state',
                't1.county',
                $db->raw('SUM(`t2`.`price`) / SUM(`t2`.`area`) AS `avg`'),
                $db->raw('STDDEV_SAMP(`t2`.`price_per_acre`) AS `stddev`'),
            ])->joinSub($cte, 't2', function (JoinClause $join) use ($db) {
                $join->on('t2.area', '>=', $db->raw('`t1`.`area` * ' . $this->area_min_ratio))
                    ->on('t2.area', '<=', $db->raw('`t1`.`area` * ' . $this->area_max_ratio))
                    ->on('t2.county', '=', 't1.county')
                    ->on('t2.state', '=', 't1.state');
            })->whereIn('status', [Listing::STATUS_AVAILABLE, Listing::STATUS_UNDER_CONTRACT])
            ->where('t1.id', '>', $this->startingId)
            ->when($this->recheck, static function (Builder $query) {
                return $query->whereNull('t1.local_avg_price_per_acre');
            })
            ->groupBy('t1.id')
            ->orderBy('t1.id')
            ->limit($this->limit);

        $locals = $db->query()->fromSub($t1, 'local_avg')
            ->select([
                'local_avg.id',
                'local_avg.avg',
                $db->raw('MIN(`local_min`.`price_per_acre`) AS `min`'),
            ])
            ->joinSub($cte, 'local_min', function (JoinClause $join) use ($db) {
                $join->on('local_min.area', '>=', $db->raw('`local_avg`.`area` * ' . $this->area_min_ratio))
                    ->on('local_min.area', '<=', $db->raw('`local_avg`.`area` * ' . $this->area_max_ratio))
                    ->on('local_min.county', '=', 'local_avg.county')
                    ->on('local_min.state', '=', 'local_avg.state')
                    ->on('local_min.price_per_acre', '>=', $db->raw('`avg` - 2 * `stddev`'))
                    ->on('local_min.price_per_acre', '<=', $db->raw('`avg` + 2 * `stddev`'));
            })->groupBy('local_avg.id')
            ->having($db->raw('COUNT(`local_min`.`id`)'), '>=', $this->minimum_number_of_locals);

        $rowsUpdated = $db->table('listings', 'main')
            ->joinSub($locals, 'locals', 'main.id', '=', 'locals.id')
            ->whereRaw('LAST_INSERT_ID(`main`.`id`)')
            ->update([
                'main.local_avg_price_per_acre' => $db->raw('`locals`.`avg`'),
                'main.local_min_price_per_acre' => $db->raw('`locals`.`min`'),
            ]);

        $logger->debug("Rows updated: $rowsUpdated");
        $logger->debug("Starting ID: " . $this->startingId);
        $newLastId = ((array) $db->select('SELECT LAST_INSERT_ID() AS `last_id`')[0])['last_id'];
        $logger->debug("Last Insert ID: $newLastId");

        $subQuery = $db->table('listings')
            ->select('id')
            ->whereIn('status', [Listing::STATUS_AVAILABLE, Listing::STATUS_UNDER_CONTRACT])
            ->where('id', '>', $newLastId)
            ->when($this->recheck, static function (Builder $query) {
                return $query->whereNull('local_avg_price_per_acre');
            })->orderBy('id')
            ->limit($this->limit);

        $remaining = $db->query()->fromSub($subQuery, 'remaining')
            ->count('id');
        $logger->debug("Remaining: $remaining");

        if ($remaining > 0 && $rowsUpdated !== 0) {
            $this->batch()->add(
                [
                    new self(
                        $newLastId,
                        $this->limit,
                        $this->area_min_ratio,
                        $this->area_max_ratio,
                        $this->minimum_number_of_locals,
                        $this->recheck
                    ),
                ]
            );
        } elseif (!$this->recheck) {
            // We ended our first run, now we need to recheck with wider coverage
            $this->batch()->add(
                [
                    new self(
                        0,
                        $this->limit,
                        0.25,
                        1.75,
                        3,
                        true
                    ),
                ]
            );
        }
    }
}
