<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// auth routes
Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->middleware(RedirectIfAuthenticated::class)
    ->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware(RedirectIfAuthenticated::class);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware(Authenticate::class)
    ->name('logout');


// app routes
Route::middleware(['auth'])->group(static function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/listings', [ListingController::class, 'index'])->name('listings');
    Route::get('/listings/download', [ListingController::class, 'download'])->name('listings.download');
    Route::get('/listings/subscribe', [ListingController::class, 'subscribe'])->name('listings.subscribe');
    Route::get('/listings/unsubscribe', [ListingController::class, 'unsubscribe'])->name('listings.unsubscribe');
    Route::get('/listings/{listing}', [ListingController::class, 'show'])->name('listing');
    Route::post('/listings', [ListingController::class, 'store'])->name('listing.store');

    Route::resource('users', UserController::class)
        ->except(['show', 'update']);
    Route::get('/subscription/{subscription}/listings', [SubscriptionController::class, 'listings'])->name('subscription.listings');
    Route::resource('subscriptions', SubscriptionController::class)
        ->only(['index', 'destroy']);
});
