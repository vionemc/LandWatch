<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */

declare(strict_types=1);

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use JetBrains\PhpStorm\Pure;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

use function array_map;
use function array_pop;
use function array_rand;
use function array_search;
use function array_values;
use function count;
use function http_build_query;
use function json_encode;
use function preg_split;
use function sleep;
use function time;

use const CURLE_COULDNT_CONNECT;
use const CURLE_COULDNT_RESOLVE_HOST;
use const CURLE_COULDNT_RESOLVE_PROXY;
use const CURLE_GOT_NOTHING;
use const CURLE_OPERATION_TIMEOUTED;
use const CURLE_SSL_CACERT;
use const CURLE_SSL_CONNECT_ERROR;
use const CURLE_SSL_PEER_CERTIFICATE;

final class ProxyClient
{
    private const DEFAULT_PARAMS = [
        'headers' => [
            'Accept-Encoding' => 'gzip,deflate',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Cache-Control' => 'no-cache',
            'Preferanonymous' => 1,
        ],
        'connect_timeout' => 15,
        'read_timeout' => 30,
        'timeout' => 30,
    ];

    private const USER_AGENTS_FILE = 'shared/user-agents.json';
    private const DEFAULT_USER_AGENT ='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.114 Safari/537.36 Edg/89.0.774.68';

    private const PROXY_LIST_FILE = 'shared/proxy-list.json';

    private ClientInterface $client;
    private ?array $userAgents = null;
    private ?array $proxyList = null;

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws FileNotFoundException
     */
    public function __construct(private Filesystem $storage, private LoggerInterface $logger)
    {
        $this->client = new Client(self::DEFAULT_PARAMS);

        $this->updateUserAgentsList();
        if ($this->storage->exists(self::USER_AGENTS_FILE)) {
            /** @noinspection JsonEncodingApiUsageInspection, PhpUnhandledExceptionInspection */
            $this->userAgents = json_decode($this->storage->get(self::USER_AGENTS_FILE), false);
        }

        $this->updateProxyList(4 * 60 * 60);
        if ($this->storage->exists(self::PROXY_LIST_FILE)) {
            /** @noinspection JsonEncodingApiUsageInspection */
            $this->proxyList = $this->getProxyList();
        }
    }

    /**
     * @throws ClientExceptionInterface|JsonException|FileNotFoundException
     * @throws Exception
     */
    public function get(string $url, array $query = [], int $retries = 5, int $delay = 0, bool $forceProxy = false): string
    {
        if ($forceProxy) {
            $proxy = null;
            while ($proxy === null) {
                $proxy = $this->getRandomProxy();
            }
        } else {
            $proxy = $this->getRandomProxy();
        }

        try {
            $response = $this->request($url, 'GET', $query, proxy: $proxy ?? null);
        } catch (ClientExceptionInterface $e) {
            if ($proxy !== null) {
                if ($e::class === ClientException::class) {
                    if ($e->getCode() === 403) {
                        $this->removeProxyFromList($proxy);
                    } else if ($e->getCode() === 404) {
                        throw $e;
                    } else {
                        $this->logger->debug("Retrying decreasing tries (error code: {$e->getCode()}).");
                        --$retries;
                    }
                } else if ($e::class === ConnectException::class) {
                    /** @var ConnectException $e */
                    $error = $e->getHandlerContext()['errno'];
                    // CURLE_SSL_CONNECT_ERROR - probably due to SSL handshake with proxy
                    // CURLE_COULDNT_RESOLVE_HOST - usually due to proxy if you are sure on host
                    // CURLE_OPERATION_TIMEOUTED - Timeout, the proxy can work in the future, it can be just overloaded
                    $proxyErrors = [
                        CURLE_SSL_PEER_CERTIFICATE,
                        CURLE_COULDNT_CONNECT,
                        CURLE_SSL_CONNECT_ERROR,
                        CURLE_COULDNT_RESOLVE_HOST,
                        CURLE_OPERATION_TIMEOUTED,
                    ];
                    if (in_array($error, $proxyErrors, true)) {
                        $this->removeProxyFromList($proxy);
                    } else if ($error === CURLE_GOT_NOTHING) {
                        // We have received empty response probably due to overloading target
                        $this->logger->debug("Empty response, add delay and retry.");
                        $delay = 15;
                    } else if ($error !== CURLE_OPERATION_TIMEOUTED) {
                        $this->logger->debug("Retrying decreasing tries (error code: $error).");
                        --$retries;
                    } else {
                        $this->logger->debug("Retrying without decreasing tries (error code: $error).");
                    }
                } else if ($e::class === RequestException::class) {
                    /** @var RequestException $e */
                    $error = $e->getHandlerContext()['errno'];
                    // 97 - connection to proxy closed, or unable to receive initial response
                    // CURLE_SSL_CACERT - SSL certificate problem, usually self signed certificate on proxy, if you are sure on host
                    if ($error === 97 || $error === CURLE_COULDNT_RESOLVE_PROXY || $error === CURLE_SSL_CACERT) {
                        $this->removeProxyFromList($proxy);
                    } else {
                        $this->logger->debug("Retrying decreasing tries (error code: $error).");
                        --$retries;
                    }
                } else if ($e::class === ServerException::class && ($e->getCode() === 502 || $e->getCode() === 500)) {
                    // We have received "502 Bad Gateway response" or "500 Internal Server Error" probably due to overloading target
                    $this->logger->debug("502 bad gateway or 500 internal error. Add delay and retry.");
                    $delay = 15;
                } else {
                    $this->logger->debug("Retrying decreasing tries (error code: {$e->getCode()}).");
                    --$retries;
                }
            } else {
                $this->logger->debug("Retrying decreasing tries (error code: {$e->getCode()}).");
                --$retries;
            }

//            if ($retries > 0) {
//                //$this->logger->debug("Retrying request after $delay seconds...");
//                //sleep($delay);
//
//                throw new ProxyClientException($e->getMessage(), $e->getCode(), $e);
//                //return $this->get($url, $query, $retries, $delay);
//            }

            if ($proxy === null) {
                throw new Exception("Http Exception without proxy.", $e->getCode(), $e);
            }

            throw new ProxyClientException($e->getMessage(), $e->getCode(), $e);
        }

        return $response->getBody()->getContents();
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $query
     * @param bool $randomUserAgent
     * @param string|null $proxy
     * @return ResponseInterface
     * @throws GuzzleException
     * @noinspection PhpSameParameterValueInspection
     */
    private function request(string $url, string $method = 'GET', array $query = [], bool $randomUserAgent = true, ?string $proxy = null): ResponseInterface
    {
        $fullUrl = $this->buildUrl($url, $query);
        if ($proxy !== null) {
            $this->logger->debug("Sending '$method' request to $fullUrl, through $proxy");
        } else {
            $this->logger->debug("Sending '$method' request to $fullUrl");
        }
        $headers = [];
        if ($randomUserAgent) {
            $headers['User-Agent'] = $this->getRandomUserAgent();
        }

        return $this->client->request($method, $url, [
            'headers' => $headers,
            'query' => $query,
            'proxy' => $proxy,
            'http_errors' => true,
        ]);
    }

    #[Pure]
    private function buildUrl(string $url, array $queryOptions = []): string
    {
        if (count($queryOptions) === 0) {
            return $url;
        }

        return $url . '?' . http_build_query($queryOptions);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    private function getRandomProxy(): ?string
    {
        $proxy = null;

        // Read random User-Agent from the list
        if ($this->proxyList !== null) {
            if (count($this->proxyList) === 0) {
                // force update it proxy list is empty for some reason
                $this->updateProxyList(0);
            }
            $proxy = count($this->proxyList) > 0 ? $this->proxyList[array_rand($this->proxyList)] : null;
        }

        return $proxy;
    }

    private function getRandomUserAgent(): string
    {
        $userAgent = self::DEFAULT_USER_AGENT;

        // Read random User-Agent from the list
        if ($this->userAgents !== null) {
            $userAgent = $this->userAgents[array_rand($this->userAgents)];
        }

        return $userAgent;
    }

    /**
     * @throws ClientExceptionInterface
     */
    private function updateUserAgentsList(int $ifOlderThan = 24 * 60 * 60): void
    {
        $exists = $this->storage->exists(self::USER_AGENTS_FILE);
        if (!$exists || ((time() - $this->storage->lastModified(self::USER_AGENTS_FILE)) >= $ifOlderThan)) {
            $response = $this->client->post('https://user-agents.net/download', [
                'form_params' => [
                    'browser_bits' => 64,
                    'renderingengine_name' => 'webkit',
                    'browser_type' => 'browser',
                    'platform' => 'linux',
                    'download' => 'json',
                ],
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() === 200) {
                $this->storage->put(self::USER_AGENTS_FILE, $response->getBody());
            }
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws Exception
     */
    private function updateProxyList(int $ifOlderThan = 24 * 60 * 60): void
    {
        $exists = $this->storage->exists(self::PROXY_LIST_FILE);
        if (!$exists || ((time() - $this->storage->lastModified(self::PROXY_LIST_FILE)) >= $ifOlderThan)) {
            $proxies = [];
            $query = [
                'request' => 'getproxies',
                // 'protocol' => 'socks4',
                // 'anonymity' => 'elite',
                // 'ssl' => 'yes',
                'timeout' => 5000,
                // 'country' => 'all',
                'simplified' => true,
            ];
            $protocols = ['socks4' => 'socks4://', 'socks5' => 'socks5://'];
            foreach ($protocols as $protocol => $prefix) {
                $query['protocol'] = $protocol;
                $response = $this->client->get('https://api.proxyscrape.com/v2/', [
                    'query' => $query,
                    'http_errors' => false,
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode === 200) {
                    $currentProxies = array_map(
                        static fn($value) => "$prefix$value",
                        preg_split('/\r?\n/', ((string)$response->getBody()))
                    );
                    // Remove last empty element
                    array_pop($currentProxies);
                    $proxies = [...$proxies, ...$currentProxies];
                } else if ($statusCode >= 400 && $statusCode <= 500) {
                    // We may hit daily limit, use the cached version
                    $proxies = $this->getProxyList();
                    $this->logger->debug('Using cached proxy list.');
                } else {
                    throw new Exception("Failed to receive proxies, code: $statusCode.", $statusCode);
                }
            }
            $this->logger->debug('Updating proxy list.');
            $this->storage->put(self::PROXY_LIST_FILE . '_cache', json_encode($proxies, JSON_THROW_ON_ERROR));
            $this->storage->put(self::PROXY_LIST_FILE, json_encode($proxies, JSON_THROW_ON_ERROR));
        }
    }

    /**
     * @param string $proxy
     * @throws ClientExceptionInterface
     * @throws FileNotFoundException
     * @throws JsonException
     */
    private function removeProxyFromList(string $proxy): void
    {
        $this->logger->debug("Removing $proxy from proxy list.");
        if (count($this->proxyList) < 10) {
            $this->updateProxyList(0);
        } else {
            // Re-read the proxy list, before removing one from it
            $this->proxyList = $this->getProxyList();
            $key = array_search($proxy, $this->proxyList, true);
            if ($key !== false) {
                unset($this->proxyList[$key]);
                $this->storage->put(self::PROXY_LIST_FILE, json_encode(array_values($this->proxyList), JSON_THROW_ON_ERROR));
            }
        }
        $this->logger->debug(count($this->proxyList) . ' proxies remaining in the list.');
    }

    /**
     * @throws FileNotFoundException
     * @throws JsonException
     */
    private function getProxyList(): mixed
    {
        $contents = $this->storage->get(self::PROXY_LIST_FILE);
        if ($contents === '') {
            return [];
        }

        /** @noinspection JsonEncodingApiUsageInspection */
        return json_decode($contents, false, flags: JSON_THROW_ON_ERROR);
    }
}
