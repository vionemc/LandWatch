<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Listing;
use App\Services\LandWatchAPIScraper;
use App\Services\ProxyClientException;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;

use function array_map;
use function array_merge;
use function count;
use function implode;
use function ksort;

final class UpsertListings implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use Batchable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    public function __construct(private string $url)
    {
        //
    }

    /**
     * @throws FileNotFoundException
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    public function handle(LandWatchAPIScraper $scraper, Repository $cache): void
    {
        if ($this->batch() !== null && $this->batch()->canceled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        try {
            $data = $scraper->getEntriesFrom($this->url);
        } catch (ProxyClientException) {
            if ($this->attempts() === $this->tries) {
                $this->delete();

                $this->batch()->add([new self($this->url)]);

                return;
            }

            // Silently release back, not to show errors
            $this->release();
            return;
        }

        $timestamp = (new Listing())->freshTimestampString();

        // Get entries, add timestamps to values
        $entries = array_map(static function (array $entry) use ($timestamp) {
            return array_merge([
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
                'checked_at' => $timestamp
            ], $entry);
        }, $data);

        // We may receive empty array due to filters applied on entries
        if (count($entries) === 0) {
            return;
        }

        foreach ($entries as $key => $value) {
            ksort($value);
            $entries[$key] = $value;
        }

        // Build query
        $query = Listing::getQuery();
        $bindings = $query->cleanBindings(Arr::flatten($entries, 1));
        $grammar = $query->getGrammar();
        $sql = $grammar->compileInsert($query, $entries) . ' on duplicate key update ';
        $update = [
            '`updated_at` = IF(`price` <> values(`price`) OR `status` <> values(`status`), values(`updated_at`), `updated_at`)',
            '`price` = values(`price`)',
            '`status` = values(`status`)',
            '`checked_at` = values(`checked_at`)',
        ];
        $sql .= implode(', ', $update);

        // Run query
        $query->getConnection()->affectingStatement($sql, $bindings);
        $cache->forget('dashboard_totals');
    }
}
