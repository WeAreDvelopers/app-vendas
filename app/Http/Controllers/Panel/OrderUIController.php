<?php
namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderUIController extends Controller {
    public function index(Request $r) {
        $orders = DB::table('orders')->orderByDesc('id')->paginate(20)->withQueryString();
        return view('panel.orders.index', compact('orders'));
    }
}
