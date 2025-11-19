<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\SupplierImport;
use App\Models\ImportError;

class ImportSupplierFile implements ShouldQueue {
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $tries = 3;
  public $backoff = [30, 120, 600];

  private array $errors = [];
  private int $validRows = 0;

  public function __construct(public int $importId) {}

  public function handle(): void {
    $import = SupplierImport::with('supplier.mapping')->find($this->importId);
    if (!$import) return;

    $import->update(['status' => 'processing']);

    $path = Storage::disk('local')->path($import->source_file);
    $ext = strtolower($import->source_type);

    $rows = [];
    $rowNumber = 1; // Linha do cabeçalho

    try {
      if (in_array($ext, ['xlsx', 'csv'])) {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);

        // Detecta cabeçalho (primeira linha)
        $header = array_shift($data);
        $map = $this->buildHeaderMap($header);

        // Obtém mapeamentos customizados do fornecedor (se existir)
        $customMappings = null;
        if ($import->supplier && $import->supplier->mapping) {
          $customMappings = $import->supplier->mapping->column_mappings;
        }

        foreach ($data as $line) {
          $rowNumber++;

          // Tenta extrair dados da linha
          $rowData = $this->extractRowData($line, $map, $customMappings);

          // Valida linha
          $validation = $this->validateRow($rowData, $rowNumber);

          if (!$validation['valid']) {
            $this->registerError($rowNumber, $validation['error_type'], $validation['error_message'], $rowData);
            continue;
          }

          $rows[] = $rowData;
          $this->validRows++;
        }
      } else {
        // PDF: manter placeholder
        $rows = [
          ['sku' => 'PDF-SKU-001', 'ean' => null, 'name' => 'Produto PDF Exemplo', 'brand' => null, 'cost_price' => null, 'sale_price' => null],
        ];
        $this->validRows = 1;
      }

      // Persiste linhas válidas
      foreach ($rows as $row) {
        DB::table('products_raw')->insert([
          'supplier_import_id' => $this->importId,
          'sku' => $row['sku'] ?? null,
          'ean' => $row['ean'] ?? null,
          'name' => $row['name'] ?? null,
          'brand' => $row['brand'] ?? null,
          'cost_price' => $row['cost_price'] ?? null,
          'sale_price' => $row['sale_price'] ?? null,
          'status' => 'raw',
          'created_at' => now(),
          'updated_at' => now()
        ]);
      }

      // Salva erros no banco
      $this->saveErrors();

      $import->update([
        'status' => 'done',
        'total_rows' => $rowNumber - 1,
        'processed_rows' => $this->validRows
      ]);

      // Dispatcha jobs de enriquecimento apenas para linhas válidas
      foreach ($rows as $row) {
        if (!empty($row['sku'])) {
          \App\Jobs\EnrichProduct::dispatch($this->importId, $row['sku']);
        }
      }
    } catch (\Exception $e) {
      $import->update([
        'status' => 'failed',
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  private function buildHeaderMap(array $header): array {
    $map = [];
    foreach ($header as $colLetter => $label) {
      $norm = mb_strtolower(trim((string)$label));
      $map[$norm] = $colLetter;
    }
    return $map;
  }

  private function extractRowData(array $line, array $map, ?array $customMappings): array
  {
    return [
      'sku' => $this->pick($line, $map, $customMappings['sku'] ?? ['sku', 'codigo', 'cod', 'product_code']),
      'ean' => $this->pick($line, $map, $customMappings['ean'] ?? ['ean', 'ean13', 'gtin', 'barcode']),
      'name' => $this->pick($line, $map, $customMappings['name'] ?? ['name', 'descricao', 'descrição', 'produto', 'title']),
      'brand' => $this->pick($line, $map, $customMappings['brand'] ?? ['brand', 'marca', 'fabricante']),
      'cost_price' => $this->num($this->pick($line, $map, $customMappings['cost_price'] ?? ['cost', 'custo', 'preco_custo', 'preço_custo'])),
      'sale_price' => $this->num($this->pick($line, $map, $customMappings['sale_price'] ?? ['price', 'preco', 'preço', 'preco_venda', 'preço_venda'])),
    ];
  }

  private function validateRow(array $rowData, int $rowNumber): array
  {
    // Verifica se tem pelo menos SKU ou EAN
    if (empty($rowData['sku']) && empty($rowData['ean'])) {
      return [
        'valid' => false,
        'error_type' => 'missing_identifier',
        'error_message' => 'Linha sem SKU ou EAN (identificador obrigatório)'
      ];
    }

    // Verifica se tem nome do produto
    if (empty($rowData['name'])) {
      return [
        'valid' => false,
        'error_type' => 'missing_name',
        'error_message' => 'Linha sem nome do produto'
      ];
    }

    return ['valid' => true];
  }

  private function registerError(int $rowNumber, string $errorType, string $errorMessage, array $rowData): void
  {
    $this->errors[] = [
      'row_number' => $rowNumber,
      'error_type' => $errorType,
      'error_message' => $errorMessage,
      'row_data' => $rowData,
    ];
  }

  private function saveErrors(): void
  {
    foreach ($this->errors as $error) {
      ImportError::create([
        'supplier_import_id' => $this->importId,
        'row_number' => $error['row_number'],
        'error_type' => $error['error_type'],
        'error_message' => $error['error_message'],
        'row_data' => $error['row_data'],
      ]);
    }
  }

  private function pick(array $line, array $map, array $candidates) {
    foreach ($candidates as $cand) {
      $candNorm = mb_strtolower($cand);
      if (isset($map[$candNorm])) {
        $col = $map[$candNorm];
        return $line[$col] ?? null;
      }
    }
    // tentativa por heurística: procura substring
    foreach ($map as $label => $col) {
      foreach ($candidates as $cand) {
        if (str_contains($label, mb_strtolower($cand))) {
          return $line[$col] ?? null;
        }
      }
    }
    return null;
  }

  private function num($v) {
    if ($v === null) return null;
    $s = str_replace(['.', ',',' '], ['','',''], (string)$v);
    // tenta detectar formato brasileiro
    $v2 = str_replace(['.', ' '], ['',''], (string)$v);
    $v2 = str_replace(',', '.', $v2);
    return is_numeric($v2) ? (float)$v2 : null;
  }
}
