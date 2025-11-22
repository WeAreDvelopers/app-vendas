<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\MercadoLivreListing;
use App\Observers\MercadoLivreListingObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Registra observers
        MercadoLivreListing::observe(MercadoLivreListingObserver::class);
    }
}
