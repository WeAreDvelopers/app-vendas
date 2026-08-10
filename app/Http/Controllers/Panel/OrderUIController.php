<?php
namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderUIController extends Controller {
    public function index(Request $r) {
        $orders = Order::orderByDesc('id')->paginate(20)->withQueryString();
        return view('panel.orders.index', compact('orders'));
    }
}
