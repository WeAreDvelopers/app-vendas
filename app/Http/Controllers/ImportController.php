<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Jobs\ImportSupplierFile;

class ImportController extends Controller {
  public function store(Request $r) {
    $r->validate([
      'supplier_name' => 'required|string',
      'file' => 'required|file|mimes:xlsx,csv,pdf'
    ]);

    $path = $r->file('file')->store('supplier_imports');
    $type = strtolower($r->file('file')->getClientOriginalExtension());

    $id = DB::table('supplier_imports')->insertGetId([
      'supplier_name' => $r->supplier_name,
      'source_file'   => $path,
      'source_type'   => in_array($type,['csv','xlsx']) ? $type : 'pdf',
      'status'        => 'queued',
      'mapping'       => $r->input('mapping') ?: null,
      'created_at'    => now(), 'updated_at' => now(),
    ]);

    ImportSupplierFile::dispatch($id);
    return response()->json(['ok'=>true,'import_id'=>$id]);
  }
}
