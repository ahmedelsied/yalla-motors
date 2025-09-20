<?php

namespace App\Providers;

use App\Utils\ModelFilters;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        
        $this->app->when(ModelFilters::class)
            ->needs('$filters')
            ->give(function (Application $app) {
                /** @var Request $request */
                $request = $app->make(Request::class);

                return $request->all();
            });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
