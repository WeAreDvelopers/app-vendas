<?php
namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\Request;

class ListingUIController extends Controller {
    public function index(Request $r) {
        $status = $r->get('status');

        $query = Listing::query()
            ->leftJoin('products', 'products.id', '=', 'listings.product_id')
            ->select('listings.*', 'products.name as product_name', 'products.sku');

        if ($status) {
            $query->where('listings.status', $status);
        }

        $listings = $query->orderByDesc('listings.id')->paginate(24)->withQueryString();
        $statuses = ['draft', 'ready', 'queued', 'published', 'paused', 'error'];

        return view('panel.listings.index', compact('listings', 'statuses', 'status'));
    }
}
