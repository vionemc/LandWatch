<?php

declare(strict_types=1);

namespace App\DataGrid;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

final class DataGridServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @param BladeCompiler $blade
     * @return void
     */
    public function boot(BladeCompiler $blade): void
    {
        $blade->componentNamespace('App\\DataGrid\\View\\Components', 'data-grid');
    }
}
