<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\LandWatchAPIScraper;
use App\Services\ProxyClientException;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;

final class GetEntryPages implements ShouldQueue
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
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws FileNotFoundException
     */
    public function handle(LandWatchAPIScraper $scraper): void
    {
        if ($this->batch() !== null && $this->batch()->canceled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        try {
            $filters = $scraper->getFilterSections($this->url, 0);
        } catch (ProxyClientException) {
            if ($this->attempts() === $this->tries) {

                $this->batch()->decrementPendingJobs($this->job->uuid());
                $this->batch()->add([new self($this->url)]);

                return;
            }

            // Silently release back, not to show errors
            $this->release();
            return;
        }

        foreach ($filters as $filter) {
            $url = $filter['url'];
            if ($filter['count'] < 10000) {
                $this->batch()->add([new GetPaginatedListingPages($url)]);
            } else {
                $this->batch()->add([new self($url)]);
            }
        }

    }
}
