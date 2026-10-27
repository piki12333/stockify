<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use App\Models\ApplicationSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class NavbarDashboard extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $user = Auth::user();

        return view('components.navbar-dashboard', [
            'recentNotifications' => $user?->notifications()->latest()->limit(5)->get() ?? collect(),
            'unreadNotificationCount' => $user?->unreadNotifications()->count() ?? 0,
            'applicationSettings' => ApplicationSetting::current(),
        ]);
    }
}
