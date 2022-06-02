<?php

declare(strict_types=1);

namespace App\Jobs;

use GuzzleHttp\Psr7\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;

use function http_build_query;

final class UpdateUserAgentsFile implements ShouldQueue
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
        //
    }

    /**
     * Execute the job.
     *
     * @param ClientInterface $http
     * @param Filesystem $storage
     * @return void
     * @throws ClientExceptionInterface
     */
    public function handle(ClientInterface $http, Filesystem $storage): void
    {
        $params = [
            'browser_bits' => 64,
            'renderingengine_name' => 'webkit',
            'browser_type' => 'browser',
            'platform' => 'linux',
            'download' => 'json',
        ];
        $request = new Request('POST', 'https://user-agents.net/download', [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], http_build_query($params));
        $response = $http->sendRequest($request);
        if ($response->getStatusCode() === 200) {
            $storage->put('user-agents.json', $response->getBody());
        }
    }
}
