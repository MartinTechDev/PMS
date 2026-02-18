<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class PmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Services\Pms\PmsClientInterface::class,
            \App\Services\Pms\PmsClient::class
        );
    }

    public function boot(): void
    {
        Http::macro('pms', function () {
            return Http::baseUrl(config('pms.base_url'))
                ->timeout(config('pms.timeout'))
                ->retry(config('pms.retry_times'), 200);
        });
    }
}
