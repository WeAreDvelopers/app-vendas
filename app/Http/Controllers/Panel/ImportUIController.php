<?php
namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ImportSupplierFile;

class ImportUIController extends Controller {
    public function index(Request $r) {
        $search = trim($r->get('q', ''));
        $companyId = auth()->user()->current_company_id;

        $query = DB::table('supplier_imports')
            ->where('company_id', $companyId);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('supplier_name', 'like', "%$search%")
                  ->orWhere('id', 'like', "%$search%")
                  ->orWhere('source_type', 'like', "%$search%")
                  ->orWhere('status', 'like', "%$search%");
            });
        }

        $imports = $query->orderByDesc('id')
              ->paginate(12)
              ->withQueryString();

        $suppliers = \App\Models\Supplier::where('company_id', $companyId)
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('panel.imports.index', [
            'imports' => $imports,
            'suppliers' => $suppliers,
            'search' => $search
        ]);
    }

    public function store(Request $r) {

        $r->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_name' => 'nullable|required_without:supplier_id|string',
            'file' => 'required|file|mimes:xlsx,csv,pdf'
        ]);

        $supplierId = $r->supplier_id;
        $supplierName = $r->supplier_name;

        // Se fornecedor foi selecionado, pega o nome dele
        if ($supplierId) {
            $supplier = \App\Models\Supplier::find($supplierId);
            $supplierName = $supplier->name;
        }

        $path = $r->file('file')->store('supplier_imports', 'local');
        $type = strtolower($r->file('file')->getClientOriginalExtension());
        $id = DB::table('supplier_imports')->insertGetId([
            'company_id'    => auth()->user()->current_company_id,
            'supplier_id'   => $supplierId,
            'supplier_name' => $supplierName,
            'source_file'   => $path,
            'source_type'   => in_array($type,['csv','xlsx']) ? $type : 'pdf',
            'status'        => 'queued',
            'mapping'       => $r->input('mapping') ?: null,
            'created_at'    => now(), 'updated_at' => now(),
        ]);
        ImportSupplierFile::dispatch($id);
        return back()->with('ok','Importação enviada para a fila!');
    }

    public function show(int $id, Request $r) {
        $imp = DB::table('supplier_imports')->find($id);
        abort_unless($imp, 404);

        $search = trim($r->get('q', ''));

        $query = DB::table('products_raw')->where('supplier_import_id', $id);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('sku', 'like', "%$search%")
                  ->orWhere('ean', 'like', "%$search%")
                  ->orWhere('brand', 'like', "%$search%")
                  ->orWhere('status', 'like', "%$search%");
            });
        }

        $rows = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $errorsCount = DB::table('import_errors')->where('supplier_import_id', $id)->count();

        return view('panel.imports.show', compact('imp', 'rows', 'errorsCount', 'search'));
    }

    public function errors(int $id) {
        $imp = DB::table('supplier_imports')->find($id);
        abort_unless($imp, 404);

        $errors = DB::table('import_errors')
            ->where('supplier_import_id', $id)
            ->orderBy('row_number')
            ->paginate(50)
            ->withQueryString();

        return view('panel.imports.errors', compact('imp', 'errors'));
    }

    public function exportErrors(int $id) {
        $imp = DB::table('supplier_imports')->find($id);
        abort_unless($imp, 404);

        $errors = DB::table('import_errors')
            ->where('supplier_import_id', $id)
            ->orderBy('row_number')
            ->get();

        $filename = "erros_importacao_{$id}_" . date('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($errors) {
            $file = fopen('php://output', 'w');

            // BOM para UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Cabeçalho
            fputcsv($file, ['Linha', 'Tipo de Erro', 'Mensagem', 'Dados da Linha'], ';');

            // Dados
            foreach ($errors as $error) {
                $rowData = json_decode($error->row_data, true);
                $rowDataStr = '';
                if ($rowData) {
                    $parts = [];
                    foreach ($rowData as $key => $value) {
                        if ($value) {
                            $parts[] = "$key: $value";
                        }
                    }
                    $rowDataStr = implode(' | ', $parts);
                }

                fputcsv($file, [
                    $error->row_number,
                    $error->error_type,
                    $error->error_message,
                    $rowDataStr
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function processProducts(Request $r, int $id) {
        $r->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'required|exists:products_raw,id'
        ]);

        $imp = DB::table('supplier_imports')->find($id);
        abort_unless($imp, 404);

        $productIds = $r->input('product_ids');

        // Dispatch job para cada produto selecionado
        foreach ($productIds as $productId) {
            \App\Jobs\ProcessProductWithAI::dispatch($productId);
        }

        return back()->with('ok', count($productIds) . ' produto(s) enviado(s) para processamento com IA!');
    }

    public function destroy(int $id) {
        $import = DB::table('supplier_imports')->find($id);
        abort_unless($import, 404);

        try {
            // 1. Remove o arquivo de importação do storage
            if ($import->source_file) {
                Storage::disk('local')->delete($import->source_file);
            }

            // 2. Remove todos os erros relacionados
            DB::table('import_errors')->where('supplier_import_id', $id)->delete();

            // 3. Remove todos os produtos raw relacionados
            DB::table('products_raw')->where('supplier_import_id', $id)->delete();

            // 4. Remove a importação
            DB::table('supplier_imports')->where('id', $id)->delete();

            \Log::info("Importação #{$id} excluída com sucesso");

            return redirect()->route('panel.imports.index')
                ->with('ok', 'Importação excluída com sucesso!');

        } catch (\Exception $e) {
            \Log::error("Erro ao excluir importação {$id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao excluir importação: ' . $e->getMessage());
        }
    }

    public function destroyItem(int $importId, int $itemId) {
        $import = DB::table('supplier_imports')->find($importId);
        abort_unless($import, 404);

        $item = DB::table('products_raw')
            ->where('id', $itemId)
            ->where('supplier_import_id', $importId)
            ->first();
        abort_unless($item, 404);

        try {
            // Remove o item
            DB::table('products_raw')->where('id', $itemId)->delete();

            \Log::info("Item #{$itemId} da importação #{$importId} excluído com sucesso");

            return back()->with('ok', 'Item excluído com sucesso!');

        } catch (\Exception $e) {
            \Log::error("Erro ao excluir item {$itemId}: " . $e->getMessage());
            return back()->with('error', 'Erro ao excluir item: ' . $e->getMessage());
        }
    }
}
