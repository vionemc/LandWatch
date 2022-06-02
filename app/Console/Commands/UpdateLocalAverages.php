<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\BatchUpdateAverages;
use Illuminate\Console\Command;

final class UpdateLocalAverages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'landwatch:update-averages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update local price averages';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        BatchUpdateAverages::dispatch();

        return 0;
    }
}
