<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class MonitorController extends Controller {
  public function index() {
    $jobs = DB::table('jobs')->select('id','queue','attempts','available_at','created_at')->orderByDesc('id')->limit(50)->get();
    $failed = DB::table('failed_jobs')->select('id','queue','failed_at','exception')->orderByDesc('id')->limit(50)->get();
    return view('monitor.queues', compact('jobs','failed'));
  }
}
