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

        // Chia sẻ danh mục sự kiện & Thông báo cho Layout
        View::composer('layout', function ($view) {
            try {
                if (Schema::hasTable('categories')) {
                    $categories = Category::withCount(['events' => function ($q) {
                        $q->where('status', 'published');
                    }])->orderBy('name')->get();
                    $view->with('sidebarCategories', $categories);
                }

                if (auth()->check() && Schema::hasTable('app_notifications')) {
                    $user = auth()->user();
                    $unreadNotifCount = \App\Services\NotificationService::getUnreadCount($user);
                    $recentNotifs = \App\Services\NotificationService::getRecent($user, 8);
                    $hasStaffRole = $user->role === 'admin' || \App\Models\EventStaff::where('user_id', $user->id)->exists();

                    $view->with('unreadNotifCount', $unreadNotifCount)
                         ->with('recentNotifs', $recentNotifs)
                         ->with('hasStaffRole', $hasStaffRole);
                } else {
                    $view->with('unreadNotifCount', 0)
                         ->with('recentNotifs', collect())
                         ->with('hasStaffRole', false);
                }
            } catch (\Throwable $e) {
                $view->with('sidebarCategories', collect())
                     ->with('unreadNotifCount', 0)
                     ->with('recentNotifs', collect())
                     ->with('hasStaffRole', false);
            }
        });
    }
}
