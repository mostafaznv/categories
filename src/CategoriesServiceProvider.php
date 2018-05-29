<?php

namespace Mostafaznv\Categories;

use Illuminate\Support\ServiceProvider;
use Mostafaznv\Categories\Models\Category;

class CategoriesServiceProvider extends ServiceProvider
{
    const VERSION = '0.0.1';

    public function boot()
    {
        if ($this->app->runningInConsole())
        {
            $this->loadMigrationsFrom(__DIR__ . '../database/migrations');

            $this->publishes([__DIR__ . '/../config/config.php' => config_path('categories.php')], 'config');
            $this->publishes([__DIR__ . '/../database/migrations/' => database_path('migrations')], 'migrations');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'categories');

        // Bind eloquent models to IoC container
        $this->app->singleton('categories.category', $categoryModel = $this->app['config']['categories.models.category']);
        $categoryModel === Category::class || $this->app->alias('categories.category', Category::class);
    }
}