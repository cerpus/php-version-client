<?php

namespace Cerpus\VersionClient\Providers;

use Cerpus\VersionClient\VersionData;
use Illuminate\Support\ServiceProvider;
use Cerpus\VersionClient\VersionClient;

class VersioningServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(VersionClient::class, function() {
            return new VersionClient();
        });

        $this->app->bind(VersionData::class, function() {
            return new VersionData();
        });
    }
}
