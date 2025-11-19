<?php
namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListingUIController extends Controller {
    public function index(Request $r) {
        $status = $r->get('status');
        $query = DB::table('listings')->leftJoin('products','products.id','=','listings.product_id')
                 ->select('listings.*','products.name as product_name','products.sku');
        if ($status) $query->where('listings.status',$status);
        $listings = $query->orderByDesc('listings.id')->paginate(24)->withQueryString();
        $statuses = ['draft','ready','queued','published','paused','error'];
        return view('panel.listings.index', compact('listings','statuses','status'));
    }
}
