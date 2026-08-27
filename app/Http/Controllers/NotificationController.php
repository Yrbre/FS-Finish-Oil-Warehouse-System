<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()
            ->notifications()
            ->paginate(20);

        return view('pages.notifications.index', compact('notifications'));
    }

    /** AJAX: isi dropdown bell — 5 terbaru yang belum dibaca. */
    public function latest()
    {
        $user = auth()->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $user->unreadNotifications()
                ->take(5)
                ->get()
                ->map(fn($n) => [
                    'id'      => $n->id,
                    'type'    => $n->data['type'] ?? 'info',
                    'title'   => $n->data['title'] ?? 'Notifikasi',
                    'message' => $n->data['message'] ?? '',
                    'url'     => $n->data['url'] ?? route('notifications.index'),
                    'ago'     => $n->created_at->diffForHumans(),
                ]),
        ]);
    }

    /**
     * Tandai dibaca lalu arahkan ke halaman terkait.
     * Dipakai saat item di dropdown diklik.
     */
    public function read(string $id)
    {
        try {
            $notification = auth()->user()->notifications()->findOrFail($id);
            $notification->markAsRead();

            return redirect($notification->data['url'] ?? route('notifications.index'));
        } catch (\Exception $e) {
            Log::error('Gagal membuka notifikasi: ' . $e->getMessage());

            return redirect()->route('notifications.index');
        }
    }

    public function readAll()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return redirect()->back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
