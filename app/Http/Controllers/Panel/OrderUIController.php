<?php
namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderUIController extends Controller {
    public function index(Request $r) {
        $orders = Order::withCount('items')
            ->when($r->filled('status'), fn ($q) => $q->where('status', $r->string('status')))
            ->when($r->filled('q'), function ($q) use ($r) {
                $term = trim((string) $r->input('q'));
                $q->where(function ($sub) use ($term) {
                    $sub->where('ml_order_id', 'like', "%{$term}%")
                        ->orWhere('buyer_nickname', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $statuses = Order::query()->distinct()->orderBy('status')->pluck('status')->filter()->values();

        return view('panel.orders.index', compact('orders', 'statuses'));
    }

    public function show(int $id) {
        // findOrFail + CompanyScope global → 404 para pedido de outra empresa.
        $order = Order::with('items.product')->findOrFail($id);

        return view('panel.orders.show', compact('order'));
    }
}
