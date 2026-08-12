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
        // Definido pelo middleware PrintAgentToken: null = token mestre (todas as
        // empresas); id = agente escopado a uma empresa.
        $companyId = $request->attributes->get('print_company_id');

        $job = DB::table('print_jobs')
            ->where('status', 'queued')
            ->when($companyId !== null, fn ($q) => $q->where('company_id', $companyId))
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
                'company_id' => $job->company_id,
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
