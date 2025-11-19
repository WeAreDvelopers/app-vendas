<?php
namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller {
    public function index() {
        $stats = [
            'imports'  => DB::table('supplier_imports')->count(),
            'products' => DB::table('products')->count(),
            'listings' => DB::table('listings')->count(),
            'orders'   => DB::table('orders')->count(),
        ];
        $recentOrders = DB::table('orders')->orderByDesc('id')->limit(8)->get();
        return view('panel.dashboard', compact('stats','recentOrders'));
    }
}
