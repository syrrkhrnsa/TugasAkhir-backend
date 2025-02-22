<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Tanah;
use App\Observers\TanahObserver;
use App\Models\Sertifikat;
use App\Observers\SertifikatObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Tanah::observe(TanahObserver::class);
        Sertifikat::observe(SertifikatObserver::class);
    }
}