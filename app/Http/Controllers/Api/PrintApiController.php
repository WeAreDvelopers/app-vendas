<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrintApiController extends Controller
{
    // GET /api/print/next
    public function next(Request $request)
    {
        $job = DB::table('print_jobs')
            ->where('status', 'queued')
            ->orderBy('id')
            ->first();

        if (!$job) {
            return response()->json(['job' => null]);
        }

        // marca como printing
        DB::table('print_jobs')->where('id', $job->id)->update([
            'status' => 'printing',
            'attempts' => DB::raw('attempts + 1'),
            'updated_at' => now(),
        ]);

        return response()->json([
            'job' => [
                'id' => $job->id,
                'order_id' => $job->order_id,
                'type' => $job->type,
                'driver' => $job->driver,
                'payload_path' => $job->payload_path,
                'payload_raw' => $job->payload_raw,
            ]
        ]);
    }

    // POST /api/print/{id}/ack  body: { status: "printed"|"failed", error?: "..." }
    public function ack(Request $request, int $id)
    {
        $status = $request->input('status');
        $error  = $request->input('error');

        if (!in_array($status, ['printed','failed'])) {
            return response()->json(['error'=>'invalid status'], 422);
        }

        DB::table('print_jobs')->where('id',$id)->update([
            'status' => $status,
            'last_error' => $error,
            'updated_at' => now(),
        ]);

        return response()->json(['ok'=>true]);
    }
}
