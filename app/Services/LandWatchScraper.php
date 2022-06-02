<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;

use function array_filter;
use function array_map;
use function implode;
use function is_int;
use function is_string;
use function json_decode;
use function preg_match;
use function stripslashes;

final class LandWatchScraper
{
    private const URL = 'https://www.landwatch.com';

    public function __construct(private ProxyClient $crawler)
    {
        //
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException|FileNotFoundException
     */
    public function getEntries(): array
    {
        $results = [];
        $pages = $this->getEntryPages('https://www.landwatch.com/land');
        $paginatedPages = [];
        foreach ($pages as $page) {
            $paginatedPages = [...$paginatedPages, ...$this->getPaginatedUrls($page)];
        }

        foreach ($paginatedPages as $page) {
            $results = [...$results, ...$this->getEntriesFrom($page, true)];
        }

        return $results;
    }

    /**
     * @param string $url
     * @param string|int|null $section Name or index of section
     * @return array all sections or specific section if $section is set
     * @throws ClientExceptionInterface
     * @throws JsonException|FileNotFoundException
     */
    public function getFilterSections(string $url, string|int|null $section = null): array {
        $results = [];
        $response = $this->crawler->get($url, forceProxy: true);
        preg_match('/window\.serverState = "(.+)";/', $response, $matches);
        $data = json_decode(stripslashes($matches[1]), true, 512, JSON_THROW_ON_ERROR);
        $filterSections = $data['searchPage']['filterSections'];

        $remapUrl = static function (array $link) {
            return [
                'id' => $link['id'],
                'displayText' => $link['displayText'],
                'count' => $link['count'],
                'url' => self::URL . $link['relativeUrlPath']
            ];
        };

        if (is_int($section)) {
            return array_map($remapUrl, $filterSections[$section]['filterLinks']);
        }

        foreach ($data['searchPage']['filterSections'] as $filterSection) {
            $results[$filterSection['section']] =
                array_map($remapUrl, $data['searchPage']['filterSections'][0]['filterLinks']);
        }

        if (is_string($section)) {
            return $results[$section];
        }

        return $results;
    }

    /**
     * @param string $url
     * @param bool $paginated
     * @return array
     * @throws ClientExceptionInterface
     * @throws JsonException|FileNotFoundException
     */
    public function getEntryPages(string $url, bool $paginated = false): array {
        $results = [];
        $filters = $this->getFilterSections($url, 0);

        foreach ($filters as $filter) {
            if ($filter['count'] < 10000) {
                if ($paginated) {
                    $results = [...$results, ...$this->getPaginatedUrls($url)];
                } else {
                    $results[] = $url;
                }
            } else {
                $results = [...$results, ...$this->getEntryPages($url, $paginated)];
            }
        }

        return $results;
    }

    /**
     * @param string $url
     * @return array
     * @throws ClientExceptionInterface
     * @throws JsonException|FileNotFoundException
     */
    public function getPaginatedUrls(string $url): array
    {
        $results = [];
        $response = $this->crawler->get($url, forceProxy: true);
        preg_match('/window\.serverState = "(.+)";/', $response, $matches);
        $data = json_decode(stripslashes($matches[1]), true, 512, JSON_THROW_ON_ERROR);
        $results[] = $url;
        $hasPages = preg_match('/of\s(\d+)/', $data['searchPage']['searchResults']['locationSeo']['paging'], $matches) === 1;
        if ($hasPages) {
            $pages = (int) $matches[1];
            for ($page = 2; $page <= $pages; $page++) {
                $results[] = "$url/page-$page";
            }
        }

        return $results;
    }

    /**
     * @param string $url
     * @param bool $paginate
     * @return array
     * @throws ClientExceptionInterface
     * @throws JsonException|FileNotFoundException
     */
    public function getEntriesFrom(string $url, bool $paginate = false): array {
        $results = [];
        $response = $this->crawler->get($url, forceProxy: true);

        preg_match('/window\.serverState = "(.+)";/', $response, $matches);
        $data = json_decode(stripslashes($matches[1]), true, 512, JSON_THROW_ON_ERROR);
        foreach ($data['searchPage']['searchResults']['propertyResults'] as $result) {
            // Skip unnecessary results
            if (!($result['acres'] > 0) || $result['priceDisplay'] === 'Auction' || !($result['price'] > 0) || $result['priceDisplay'] === '') {
                continue;
            }

            $results[] = [
                'id' => $result['lwPropertyId'],
                'types' => implode(', ', array_filter($result['types'], 'strlen')),
                'address' => $result['address'],
                'city' => $result['city'],
                'county' => $result['county'],
                'state' => $result['state'],
                'zip' => $result['zip'],
                'latitude' => $result['latitude'],
                'longitude' => $result['longitude'],
                'price' => $result['price'],
                'area' => $result['acres'],
                'status' => $result['status'],
                'url' => self::URL . $result['canonicalUrl'],
            ];
        }

        $nextPageUrl = $data['searchPage']['searchResults']['locationSeo']['nextLink'];
        if ($paginate && $nextPageUrl !== null) {
            $results = [...$results, ...$this->getEntriesFrom(self::URL . $nextPageUrl, $paginate)];
        }

        return $results;
    }

    /**
     * @throws FileNotFoundException
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    public function getEntry(string $url): array
    {
        $response = $this->crawler->get($url, forceProxy: true);

        preg_match('/window\.serverState = "(.+)";/', $response, $matches);
        $data = json_decode(stripslashes($matches[1]), true, 512, JSON_THROW_ON_ERROR);

        $result = $data['propertyDetailPage']['propertyData'];

        return [
            'id' => $result['lwPropertyId'],
            'types' => implode(', ', array_filter($result['types'], 'strlen')),
            'address' => $result['address']['address1'],
            'city' => $result['city']['name'],
            'county' => $result['county']['name'],
            'state' => $result['state']['name'],
            'zip' => $result['address']['zip'],
            'latitude' => $result['latitude'],
            'longitude' => $result['longitude'],
            'price' => $result['price'],
            'area' => $result['acres'],
            'status' => $result['status'],
            'url' => self::URL . $result['canonicalUrl'],
        ];
    }
}
