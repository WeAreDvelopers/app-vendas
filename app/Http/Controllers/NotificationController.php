<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Busca notificações não lidas do usuário
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Busca notificações não lidas dos últimos 7 dias
        $notifications = DB::table('notifications')
            ->where('user_id', $userId)
            ->orWhereNull('user_id') // Notificações globais
            ->where('read', false)
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Conta total de não lidas
        $unreadCount = DB::table('notifications')
            ->where(function($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereNull('user_id');
            })
            ->where('read', false)
            ->count();

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Marca uma notificação como lida
     */
    public function markAsRead($id)
    {
        $userId = Auth::id();

        $notification = DB::table('notifications')
            ->where('id', $id)
            ->where(function($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereNull('user_id');
            })
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notificação não encontrada'
            ], 404);
        }

        DB::table('notifications')
            ->where('id', $id)
            ->update([
                'read' => true,
                'read_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Notificação marcada como lida'
        ]);
    }

    /**
     * Marca todas as notificações como lidas
     */
    public function markAllAsRead()
    {
        $userId = Auth::id();

        DB::table('notifications')
            ->where(function($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereNull('user_id');
            })
            ->where('read', false)
            ->update([
                'read' => true,
                'read_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Todas as notificações foram marcadas como lidas'
        ]);
    }

    /**
     * Deleta uma notificação
     */
    public function destroy($id)
    {
        $userId = Auth::id();

        $deleted = DB::table('notifications')
            ->where('id', $id)
            ->where(function($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereNull('user_id');
            })
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Notificação não encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notificação removida'
        ]);
    }

    /**
     * Cria uma notificação (helper estático)
     */
    public static function create(array $data)
    {
        return DB::table('notifications')->insertGetId([
            'user_id' => $data['user_id'] ?? null,
            'type' => $data['type'] ?? 'info', // success, info, warning, error
            'title' => $data['title'],
            'message' => $data['message'],
            'icon' => $data['icon'] ?? null,
            'action_url' => $data['action_url'] ?? null,
            'action_text' => $data['action_text'] ?? null,
            'read' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
