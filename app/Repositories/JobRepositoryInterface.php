<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Collection;

interface JobRepositoryInterface
{
    public function getPending(?string $queueName = null): Collection;

    public function countPending(?string $queueName = null): int;
}
