<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ** Custom pagination macro for API responses
        Builder::macro('apiPaginate', function (int $default = 15, int $max = 100) {
            $perPage = (int) request()->query('per_page', $default);

            if ($perPage < 1) {
                $perPage = $default;
            }
            if ($perPage > $max) {
                $perPage = $max;
            }

            return $this->paginate($perPage);
        });
    }
}