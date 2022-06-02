<?php

declare(strict_types=1);

namespace App\Repositories\Redis;

use App\Repositories\JobRepositoryInterface;
use Illuminate\Contracts\Redis\Factory as Redis;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Collection;

use function array_reduce;
use function collect;
use function config;
use function preg_grep;
use function str_ends_with;

final class JobRepository implements JobRepositoryInterface
{
    public function __construct(private readonly Redis $redis)
    {
        //
    }

    public function getPending(?string $queueName = null): Collection
    {
        // TODO: Implement getPending() method.
        return collect([]);
    }

    public function countPending(?string $queueName = null): int
    {
        /** @var PhpRedisConnection $redis */
        $redis = $this->redis->connection();
        $scan = $redis->scan(null, ['match' => config('database.redis.options.prefix') . 'queues:*']);
        if ($scan === false) {
            return 0;
        }

        $queues = preg_grep('/.*(?<!notify)$/', $scan[1]);
        $luaScript = array_reduce($queues, static function (string $script, string $value) {
            if (str_ends_with($value, 'reserved') || str_ends_with($value, 'delayed')) {
                return $script === '' ? "redis.call('zcard', '$value')" : "$script + redis.call('zcard', '$value')";
            }

            return $script === '' ? "redis.call('llen', '$value')" : "$script + redis.call('llen', '$value')";
        }, '');

        return $redis->eval("return $luaScript", 0);
    }
}
