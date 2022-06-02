<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Support\Carbon;
use Throwable;

class CheckSubscriptions implements ShouldQueue, ShouldBeUnique
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
        $this->onQueue('notifications');
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return Carbon::now()->format('d-m-Y');
    }

    /**
     * Execute the job.
     * @throws Throwable
     */
    public function handle(Dispatcher $dispatcher): void
    {
        /** @var Collection|User[] $users */
        $users = User::query()->whereHas('subscriptions')->with('subscriptions')->get();
        $jobs = [];

        foreach ($users as $user) {
            $jobs = [
                ...$jobs,
                ...$user->subscriptions->map(static fn (Subscription $subscription) => new NotifySubscriber($user, $subscription)),
            ];
        }

        $dispatcher
            ->batch($jobs)
            ->name('Check subscription and notify if needed.')
            ->then(static function (Batch $batch) {
                // All jobs completed successfully...
            })->catch(static function (Batch $batch, Throwable $e) {
                // First batch job failure detected...
            })->finally(static function () {
                // The batch has finished executing...
            })->onQueue('notifications')->dispatch();
    }
}
