<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Tandai sudah dibaca hanya notifikasi yang memang dimiliki pengguna ini.
    public function markAsRead(Request $request, string $notificationId)
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();

        $notification->markAsRead();

        return redirect()->route('transactions.index');
    }

    // Tandai semua notifikasi milik pengguna ini sebagai sudah dibaca.
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        return back();
    }
}
