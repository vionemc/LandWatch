<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Listing;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionSatisfied;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;

use Laratips\Filterable\FilteringScope;

use Psr\Container\ContainerExceptionInterface;

use Psr\Container\NotFoundExceptionInterface;

use function count;

class NotifySubscriber implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use Batchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private User $user, private Subscription $subscription)
    {
        $this->onQueue('notifications');
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->subscription->id;
    }

    /**
     * Execute the job.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(): void
    {
        if ($this->batch() !== null && $this->batch()->canceled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $filters = $this->subscription->filters;
        $query = Listing::query()
            ->where(static function (Builder $query) {
                $query
                    ->whereDate('created_at', Carbon::today())
                    ->orWhereDate('updated_at', Carbon::today());
            });
        (new FilteringScope())->apply($query, $query->getModel(), [], $filters);
        $satisfiedListings = $query->get()->toArray();
        if (count($satisfiedListings) > 0) {
            $this->user->notify(new SubscriptionSatisfied($this->subscription, $satisfiedListings));
        }
    }
}
