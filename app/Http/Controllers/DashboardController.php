<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Repositories\JobRepositoryInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

use function response;
use function view;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly JobRepositoryInterface $jobRepository,
        private readonly Repository $cache,
    ) {
        //
    }

    public function index(): View
    {
        return view('dashboard.index');
    }

    public function data(): JsonResponse
    {
        $totals = $this->cache->rememberForever('dashboard_totals', static function() {
            return Listing::selectRaw('COUNT(`id`) AS `total`, SUM( CASE WHEN DATE(`created_at`) = CURDATE() THEN 1 ELSE 0 END ) AS `total_created`, SUM( CASE WHEN DATE(`updated_at`) = CURDATE() && DATE(`updated_at`) <> DATE(`created_at`) THEN 1 ELSE 0 END ) AS `total_updated`, SUM( CASE WHEN `status` IN (1, 2) THEN 1 ELSE 0 END ) AS `total_active`')
                ->get()->first()->toArray();
        });
        $totals['pending_jobs'] = $this->jobRepository->countPending();

        return response()->json($totals);
    }
}
