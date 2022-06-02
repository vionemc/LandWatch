<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */

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
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;

use function array_column;
use function array_diff;
use function array_filter;
use function array_map;
use function array_reduce;
use function array_values;
use function count;
use function in_array;

final class CheckStaleEntries implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use Batchable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private readonly array $ids)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @throws ClientExceptionInterface
     * @throws FileNotFoundException
     * @throws JsonException
     */
    public function handle(LandWatchAPIScraper $scraper, Repository $cache): void
    {
        if ($this->batch() !== null && $this->batch()->canceled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        try {
            $data = $scraper->getEntries($this->ids);
        } catch (ProxyClientException) {
            if ($this->attempts() === $this->tries) {
                $this->delete();

                $this->batch()->add([new self($this->ids)]);

                return;
            }

            // Silently release back, not to show errors
            $this->release();
            return;
        } catch (ClientExceptionInterface $e) {
            if ($e->getCode() === 404) {
                $data = [];
            } else {
                throw $e;
            }
        }

        $entries = array_map(
            static fn(array $property) => ['id' => $property['id'], 'status' => $property['status']],
            $data
        );

        $accumulator = [];
        // Find non existent entries
        if (count($this->ids) > count($entries)) {
            $accumulator[Listing::STATUS_STALE] = array_map(
                static fn(int $id) => $id,
                array_values(array_diff($this->ids, array_column($entries, 'id')))
            );
        }

        $validStatuses = [Listing::STATUS_AVAILABLE, Listing::STATUS_UNDER_CONTRACT];
        $staleEntries = array_reduce(
            array_filter($entries, static fn($item) => !in_array($item['status'], $validStatuses, true)),
            static function(array $accumulator, array $element) {
                $accumulator[$element['status']][] = $element['id'];
                return $accumulator;
        }, $accumulator);

        foreach ($staleEntries as $status => $listingIds) {
            Listing::whereIn('id', $listingIds)
                ->update([
                    'status' => $status,
                    'checked_at' => (new Listing())->freshTimestampString(),
                ]);
        }

        if (count($staleEntries) > 0) {
            $cache->forget('dashboard_totals');
        }
    }
}
