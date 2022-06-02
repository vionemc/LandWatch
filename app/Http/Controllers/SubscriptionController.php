<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

use function back;
use function view;

class SubscriptionController extends Controller
{
    public function __construct(private Guard $auth)
    {
        //
    }

    public function index(): View
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $subscriptions = Subscription::where(['user_id' => $this->auth->id()])->sortable()->filterable()->paginate();
        $filters = [];

        return view('subscription.index', [
            'items' => $subscriptions,
            'filters' => $filters,
        ]);
    }

    public function listings(int $id): RedirectResponse
    {
        $subscription = Subscription::findOrFail($id);
        return \redirect()->route('listings', ['filter' => $subscription->filters]);
    }

    public function destroy(string $id): RedirectResponse
    {
        Subscription::where(['id' => $id])->delete();
        return back();
    }
}
