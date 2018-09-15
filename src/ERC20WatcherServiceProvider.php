<?php

namespace Leonis\ERC20Watcher;

use Illuminate\Support\ServiceProvider;

class ERC20WatcherServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(realpath(__DIR__ . '/../config/coins.php'), 'coins');
    }

    public function boot()
    {
        $this->commands([ERC20Watcher::class]);
    }
}