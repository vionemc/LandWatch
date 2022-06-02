<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Repositories\JobRepositoryInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

final class JobRepository implements JobRepositoryInterface
{
    public function __construct(private ConnectionInterface $connection)
    {
        //
    }

    public function getPending(?string $queueName = null): Collection
    {
        return $this->connection->table('jobs')->get();
    }

    public function countPending(?string $queueName = null): int
    {
        return $this->countJobsByType('pending_jobs');
    }

    protected function countJobsByType(string $type, ?string $queueName = null): int
    {
        return match ($type) {
            'pending_jobs' => $this->connection->table('jobs')
                ->when($queueName !== null, static fn (Builder $query) => $query->where(['queue' => $queueName]))
                ->count(),
        };
    }

    protected function getJobsByType(string $type, ?string $queueName = null): Collection
    {
        return match ($type) {
            'pending_jobs' => $this->connection->table('jobs')
                ->when($queueName !== null, static fn (Builder $query) => $query->where(['queue' => $queueName]))
                ->get(),
        };
    }
}
