<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Subscription;
use Exception;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use JsonException;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function array_map;
use function array_reduce;
use function back;
use function fclose;
use function fopen;
use function fputcsv;
use function http_build_query;
use function json_encode;
use function ksort;
use function redirect;
use function response;
use function sha1;
use function view;

final class ListingController extends Controller
{
    public function __construct(
        private readonly Connection $db,
        private readonly Guard $auth,
        private readonly Repository $cache
    ) {
        //
    }

    public function index(Request $request): View
    {
        $query = Listing::query();

        return view('listing.index', [
            'query' => $query,
            'filters' => $this->getFilters($request),
            'isSubscribed' => $this->isSubscribed($request),
        ]);
    }

    public function show(int $id, Request $request, Router $router): View
    {
        $listing = Listing::findOrFail($id);
        $previousRequest = Request::create($request->session()->previousUrl());
        if ($router->getRoutes()->match($previousRequest)->getName() === 'listings') {
            $request->session()->flash('backUrl', $request->session()->previousUrl());
        }

        return view('listing.show', [
            'item' => $listing,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = Listing::findOrFail($request->input('id'));
        $item->notes = $request->input('notes');
        $item->save();

        return $request->session()->has('backUrl') ? redirect($request->session()->get('backUrl')) : redirect(
            'listings'
        );
    }

    public function download(): StreamedResponse
    {
        $name = 'listings.csv';
        $headers = [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename='. $name,
        ];
        $columns = $this->db->getSchemaBuilder()->getColumnListing((new Listing())->getTable());

        return response()->stream(static function () use ($columns) {
            $file = fopen('php://output', 'wb+');
            fputcsv($file, $columns);

            try {
                $data = Listing::sortable()->filterable()->cursor();
                foreach ($data as $value) {
                    $data = $value->toArray();
                    $data['status'] = Listing::statuses()[$data['status']];
                    fputcsv($file, $data);
                }
            } catch (Exception) {
            } finally {
                fclose($file);
            }
        }, 200, $headers);
    }

    public function subscribe(Request $request): RedirectResponse|JsonResponse
    {
        $appliedFilters = $this->getSubscriptionFilters($request);
        $subscription = new Subscription();
        $subscription->name = $request->get('name');
        $subscription->user_id = $request->user()->id;
        $subscription->filters = $appliedFilters;
        $saved = $subscription->save();

        if ($request->isXmlHttpRequest()) {
            return response()->json($saved);
        }

        return back();
    }

    /**
     * @throws JsonException
     */
    public function unsubscribe(Request $request): RedirectResponse
    {
        $appliedFilters = $request->get('filter');
        unset($appliedFilters['updated_at'], $appliedFilters['created_at'], $appliedFilters['checked_at']);
        ksort($appliedFilters);
        Subscription::where('filters', json_encode($appliedFilters, JSON_THROW_ON_ERROR))->delete();
        return back();
    }

    private function isSubscribed(Request $request): bool
    {
        $isSubscribed = false;
        $requestFilters = $this->getSubscriptionFilters($request);

        if ($requestFilters !== []) {
            $isSubscribed = Subscription::where(['user_id' => $this->auth->id()])
                    ->get()->whereStrict('filters', $requestFilters)->count() > 0;
        }

        return $isSubscribed;
    }

    private function getSubscriptionFilters(Request $request): array
    {
        $requestFilters = $request->get('filter', []);
        unset($requestFilters['updated_at'], $requestFilters['created_at'], $requestFilters['checked_at']);
        ksort($requestFilters);

        return $requestFilters;
    }

    private function getFilters(Request $request): array {
        $exclude = ['area', 'price', 'price_per_acre', 'local_avg_price_per_acre', 'local_min_price_per_acre', 'price_to_local_avg', 'price_to_local_min', 'updated_at'];
        // Build cache key
        // Get query params affecting the result, exclude those that don't affect it
        $queryParams = $request->except(['sort', 'direction', 'page', ...array_map(static fn(string $value) => "filter.$value", $exclude)]);
        // Sorting query params by key (acts by reference)
        ksort($queryParams);
        // Transforming the query array to query string and hashing
        $cacheKey = $queryParams === [] ? 'listings:filters' : 'listings:filters:' . sha1(http_build_query($queryParams));

        /** @var Builder $filterQuery */
        $filterQuery = array_reduce([
            Listing::filterable(['city', ...$exclude])->select(['city as name', $this->db->raw('"city" as `type`')])->distinct()->getQuery(),
            Listing::filterable(['county', ...$exclude])->select(['county as name', $this->db->raw('"county" as `type`')])->distinct()->getQuery(),
            Listing::filterable(['state', ...$exclude])->select(['state as name', $this->db->raw('"state" as `type`')])->distinct()->getQuery(),
        ], static fn (?Builder $carry, Builder $query) => $carry === null ? $query : $carry->union($query), null);

        return $this->cache->remember($cacheKey, 60*60*24, static function () use ($filterQuery) {
            $filters = $filterQuery
                ->get()
                ->mapToGroups(static fn (object $item, int $key) => [$item->type => $item->name])
                ->toArray();

            $filters['city'] = collect($filters['city'] ?? [])->mapWithKeys(fn ($item, $key) => [$item => $item])->toArray();
            $filters['county'] = collect($filters['county'] ?? [])->mapWithKeys(fn ($item, $key) => [$item => $item])->toArray();
            $filters['state'] = collect($filters['state'] ?? [])->mapWithKeys(fn ($item, $key) => [$item => $item])->toArray();
            $filters['status'] = Listing::statuses();

            return $filters;
        });
    }
}
