<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

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
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        Carbon::macro('jaWeekday', function () {
            $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
            return $weekdays[$this->dayOfWeek];
        });
    }
}
