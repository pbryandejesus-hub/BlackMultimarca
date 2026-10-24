<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Setting;
use App\Support\Cart;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::resourceVerbs([
            'create' => 'nuevo',
            'edit' => 'editar',
        ]);

        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(Str::lower($request->string('email')->toString()).'|'.$request->ip());
        });

        View::composer('components.layouts.store', function (ViewInstance $view): void {
            $view->with([
                'navCategories' => Category::active()->get(),
                'cartCount' => app(Cart::class)->count(),
                'storeSettings' => Setting::allValues(),
            ]);
        });
    }
}
