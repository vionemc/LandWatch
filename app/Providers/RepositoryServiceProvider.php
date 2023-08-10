<?php

declare(strict_types=1);

namespace App\Providers;

// use App\Repositories\Redis\JobRepository;
use App\Repositories\Eloquent\JobRepository;
use App\Repositories\JobRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(JobRepositoryInterface::class, JobRepository::class);
    }
}
