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

  /**
   * Converte um produto importado diretamente para produto final sem IA
   *
   * @param Request $r
   * @return \Illuminate\Http\JsonResponse
   */
  public function convertWithoutAI(Request $r) {
    $r->validate([
      'product_raw_id' => 'required|integer|exists:products_raw,id',
      'description' => 'nullable|string',
      'stock' => 'nullable|integer|min:0'
    ]);

    try {
      DB::beginTransaction();

      // Busca o produto raw
      $productRaw = DB::table('products_raw')->where('id', $r->product_raw_id)->first();

      if (!$productRaw) {
        return response()->json(['error' => 'Produto não encontrado'], 404);
      }

      // Verifica se já foi convertido
      if ($productRaw->status === 'ai_processed') {
        $extra = json_decode($productRaw->extra, true);
        if (isset($extra['product_id'])) {
          return response()->json([
            'ok' => false,
            'error' => 'Este produto já foi convertido',
            'product_id' => $extra['product_id']
          ], 400);
        }
      }

      // Cria descrição básica se não fornecida
      $description = $r->input('description');
      if (empty($description)) {
        $description = $this->generateBasicDescription($productRaw);
      }

      // Obtém company_id do product_raw via import
      $import = DB::table('supplier_imports')->find($productRaw->supplier_import_id);

      // Insere o produto na tabela final
      $productId = DB::table('products')->insertGetId([
        'company_id' => $import->company_id,
        'product_raw_id' => $productRaw->id,
        'sku' => $productRaw->sku,
        'ean' => $productRaw->ean,
        'name' => $productRaw->name,
        'brand' => $productRaw->brand,
        'description' => $description,
        'price' => $productRaw->sale_price,
        'cost_price' => $productRaw->cost_price,
        'status' => 'ready',
        'stock' => $r->input('stock', 0),
        'attributes' => json_encode([
          'ai_generated' => false,
          'manual_conversion' => true,
          'source' => 'import',
        ]),
        'created_at' => now(),
        'updated_at' => now()
      ]);

      // Atualiza o status do produto raw
      $extra = json_decode($productRaw->extra ?? '{}', true);
      $extra['product_id'] = $productId;
      $extra['converted_without_ai'] = true;
      $extra['converted_at'] = now()->toIso8601String();

      DB::table('products_raw')
        ->where('id', $productRaw->id)
        ->update([
          'status' => 'ai_processed',
          'extra' => json_encode($extra),
          'updated_at' => now()
        ]);

      DB::commit();

      return response()->json([
        'ok' => true,
        'product_id' => $productId,
        'message' => 'Produto convertido com sucesso sem processamento de IA'
      ]);

    } catch (\Exception $e) {
      DB::rollBack();
      \Log::error('Erro ao converter produto sem IA: ' . $e->getMessage());

      return response()->json([
        'error' => 'Erro ao converter produto: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Gera uma descrição básica baseada nos dados disponíveis
   *
   * @param object $productRaw
   * @return string
   */
  private function generateBasicDescription($productRaw): string {
    $parts = [];

    if ($productRaw->brand) {
      $parts[] = "Marca: {$productRaw->brand}";
    }

    if ($productRaw->name) {
      $parts[] = $productRaw->name;
    }

    if ($productRaw->sku) {
      $parts[] = "SKU: {$productRaw->sku}";
    }

    if ($productRaw->ean) {
      $parts[] = "EAN: {$productRaw->ean}";
    }

    return implode("\n", $parts) ?: 'Produto sem descrição';
  }
}
