<?php

namespace Yassinya\Relation;

use Illuminate\Support\ServiceProvider;

class RelationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/relation.php', 'relation');
        
        if ($this->app->runningInConsole()) {
            $this->commands([
                RelationCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/relation.php' => config_path('relation.php'),
        ], 'config');
    }
}
