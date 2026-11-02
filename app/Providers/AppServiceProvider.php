<?php

namespace App\Providers;

use App\Models\Category;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        RateLimiter::for('end-attendance', fn (Request $r) => Limit::perMinute(20)->by((string) ($r->user()?->id ?? $r->ip())));

        // Chia sẻ danh mục sự kiện cho Sidebar
        View::composer('layout', function ($view) {
            try {
                if (Schema::hasTable('categories')) {
                    $categories = Category::withCount(['events' => function ($q) {
                        $q->where('status', 'published');
                    }])->orderBy('name')->get();
                    $view->with('sidebarCategories', $categories);
                }
            } catch (\Throwable $e) {
                $view->with('sidebarCategories', collect());
            }
        });
    }
}
