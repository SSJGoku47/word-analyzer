<?php

namespace App\Providers;

use App\Services\WordFrequencyService;
use Illuminate\Support\ServiceProvider;

class WordFrequencyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(WordFrequencyService::class, function () {
            return new WordFrequencyService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
