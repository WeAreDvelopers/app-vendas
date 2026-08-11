<?php
namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Product;
use App\Models\SupplierImport;

class DashboardController extends Controller {
    public function index() {
        $stats = [
            'imports'  => SupplierImport::count(),
            'products' => Product::count(),
            'listings' => Listing::count(),
            'orders'   => Order::count(),
        ];
        $recentOrders = Order::orderByDesc('id')->limit(8)->get();
        return view('panel.dashboard', compact('stats','recentOrders'));
    }
}
