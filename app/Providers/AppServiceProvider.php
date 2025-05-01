<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Tanah;
use App\Observers\TanahObserver;
use App\Models\Sertifikat;
use App\Observers\SertifikatObserver;
use App\Observers\PemetaanTanahObserver;
use App\Observers\PemetaanFasilitasObserver;
use App\Observers\FasilitasObserver;
use App\Models\PemetaanFasilitas;
use App\Models\PemetaanTanah;
use App\Models\Fasilitas;

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
        PemetaanTanah::observe(PemetaanTanahObserver::class);
        PemetaanFasilitas::observe(PemetaanFasilitasObserver::class);
        Fasilitas::observe(FasilitasObserver::class);
    }
}