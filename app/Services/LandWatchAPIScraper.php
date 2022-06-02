<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;

use function array_column;
use function array_combine;
use function array_filter;
use function array_map;
use function count;
use function implode;
use function is_int;
use function is_string;
use function json_decode;
use function range;

class LandWatchAPIScraper
{
    private const URL = 'https://www.landwatch.com';
    public const API_URL = self::URL . '/api/property/search/1113';

    public function __construct(private ProxyClient $client)
    {
        //
    }

    /**
     * @throws FileNotFoundException
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    public function getFilterSections(string $url, string|int|null $section = null): array {
        $response = $this->client->get($url);
        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        $filterSections = $data['filterSections'];

        $remapUrl = static function(array $link) {
            return [
                'id' => $link['id'],
                'displayText' => $link['displayText'],
                'count' => $link['count'],
                'url' => self::API_URL . $link['relativeUrlPath']
            ];
        };

        if (is_int($section)) {
            return array_map($remapUrl, $filterSections[$section]['filterLinks']);
        }

        $filterLinks = array_map(static function(array $section) use ($remapUrl) {
            return array_map($remapUrl, $section['filterLinks']);
        }, $filterSections);

        $results = array_combine(
            array_column($filterSections, 'section'),
            $filterLinks
        );

        if (is_string($section)) {
            return $results[$section];
        }

        return $results;
    }

    /**
     * @throws FileNotFoundException
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    public function getPaginatedUrls(string $url): array
    {
        $response = $this->client->get($url);
        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        $pagination = array_column($data['searchResults']['locationSeo']['paginationDataList'], 'displayString');

        // no any additional pages
        if (count($pagination) === 0) {
            return [$url];
        }


        $pages = range(2, (int) ($pagination[count($pagination) - 1]));
        return [$url, ...array_map(static fn(int $page) => "$url/page-$page", $pages)];
    }

    /**
     * @throws FileNotFoundException
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    public function getEntriesFrom(string $url): array
    {
        $response = $this->client->get($url);
        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        // Filter out unnecessary listings
        $properties = array_filter($data["searchResults"]["propertyResults"], static function(array $property) {
            return $property['acres'] > 0 && $property['price'] > 0 && $property['priceDisplay'] !== 'Auction'
                && $property['priceDisplay'] !== '';
        });

        return array_map([self::class, 'remapArrayData'], $properties);
    }

    /**
     * @throws FileNotFoundException
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    public function getEntries(array $ids): array
    {
        $url = 'https://www.landwatch.com/api/Property/getviewedlistings/' . implode('-', $ids);
        $response = $this->client->get($url);
        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        return array_map([self::class, 'remapArrayData'], $data);
    }

    private static function remapArrayData(array $data): array {
        return [
            'id' => $data['siteListingId'],
            'types' => implode(', ', array_filter($data['types'], 'strlen')),
            'address' => $data['address'],
            'city' => $data['city'],
            'county' => $data['county'],
            'state' => $data['state'],
            'zip' => $data['zip'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'price' => $data['price'],
            'area' => $data['acres'],
            'status' => $data['status'],
            'url' => self::URL . $data['canonicalUrl'],
        ];
    }
}
