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
use App\Helpers\NotificationHelper;

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

        // Lê os dados linha por linha usando getFormattedValue para evitar notação científica
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        $data = [];
        for ($row = 1; $row <= $highestRow; $row++) {
          $rowData = [];
          for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $cell = $sheet->getCell($columnLetter . $row);

            // Usa getFormattedValue() para pegar o valor como está formatado na célula
            // Isso evita a conversão automática para notação científica
            $rowData[$columnLetter] = $cell->getFormattedValue();
          }
          $data[] = $rowData;
        }

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
        // Log para debug de preços
        \Log::info("Salvando produto na importação", [
          'sku' => $row['sku'] ?? null,
          'cost_price' => $row['cost_price'] ?? null,
          'sale_price' => $row['sale_price'] ?? null
        ]);

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

      // Envia notificação de conclusão
      if ($this->validRows > 0) {
        NotificationHelper::success(
          'Importação Concluída',
          "Importação #{$import->id} finalizada! {$this->validRows} de " . ($rowNumber - 1) . " produtos processados.",
          "/panel/imports/{$import->id}",
          'Ver Detalhes'
        );
      }

      // Envia notificação se houver erros
      if (count($this->errors) > 0) {
        NotificationHelper::warning(
          'Importação com Erros',
          "Importação #{$import->id} concluída com " . count($this->errors) . " erro(s). Verifique os detalhes.",
          "/panel/imports/{$import->id}/errors",
          'Ver Erros'
        );
      }

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

      // Envia notificação de erro
      NotificationHelper::error(
        'Erro na Importação',
        "Falha ao processar importação #{$import->id}: {$e->getMessage()}",
        "/panel/imports/{$import->id}",
        'Ver Detalhes'
      );

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
    $costPriceCandidates = $customMappings['cost_price'] ?? [
      'cost', 'custo', 'preco_custo', 'preço_custo', 'preco custo', 'preço custo',
      'valor_custo', 'valor custo', 'cost_price', 'precodecompra', 'preço de compra'
    ];

    $salePriceCandidates = $customMappings['sale_price'] ?? [
      'price', 'preco', 'preço', 'preco_venda', 'preço_venda', 'preco venda', 'preço venda',
      'valor', 'valor_venda', 'valor venda', 'sale_price', 'precodevenda', 'preço de venda',
      'valor_unitario', 'valor unitário', 'valorunitario'
    ];

    return [
      'sku' => $this->pick($line, $map, $customMappings['sku'] ?? ['sku', 'codigo', 'cod', 'product_code', 'código']),
      'ean' => $this->pick($line, $map, $customMappings['ean'] ?? ['ean', 'ean13', 'gtin', 'barcode', 'codigodebarras', 'código de barras']),
      'name' => $this->pick($line, $map, $customMappings['name'] ?? ['name', 'descricao', 'descrição', 'produto', 'title', 'nome']),
      'brand' => $this->pick($line, $map, $customMappings['brand'] ?? ['brand', 'marca', 'fabricante']),
      'cost_price' => $this->num($this->pick($line, $map, $costPriceCandidates)),
      'sale_price' => $this->num($this->pick($line, $map, $salePriceCandidates)),
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
    if ($v === null || $v === '') return null;

    // Converte para string primeiro
    $original = $v;
    $v = trim((string)$v);

    // Remove espaços em branco que podem existir
    $v = preg_replace('/\s+/', '', $v);

    // Se vier em notação científica do Excel (ex: 4.86024E+17), ignora
    if (stripos($v, 'E+') !== false || stripos($v, 'E-') !== false) {
      \Log::warning("Valor em notação científica detectado e ignorado: original={$original}");
      return null;
    }

    // Remove R$, cifrão e outros símbolos monetários
    $v = preg_replace('/[R$\$]/', '', $v);

    // Remove espaços novamente após limpar símbolos
    $v = preg_replace('/\s+/', '', $v);

    // Detecta formato: se tem ponto E vírgula, assume formato brasileiro (1.234,56)
    if (strpos($v, '.') !== false && strpos($v, ',') !== false) {
      // Formato brasileiro: remove pontos (milhares) e troca vírgula por ponto (decimal)
      $v = str_replace('.', '', $v);
      $v = str_replace(',', '.', $v);
    }
    // Se tem apenas vírgula, assume decimal brasileiro (123,45)
    elseif (strpos($v, ',') !== false && strpos($v, '.') === false) {
      $v = str_replace(',', '.', $v);
    }
    // Se tem apenas ponto, precisa determinar se é separador de milhar ou decimal
    elseif (strpos($v, '.') !== false && strpos($v, ',') === false) {
      // Se tem mais de 3 dígitos depois do ponto, é provavelmente milhar (ex: 1.234)
      // Se tem 1 ou 2 dígitos depois do ponto, é decimal (ex: 123.45)
      $parts = explode('.', $v);
      if (count($parts) == 2) {
        $afterDot = strlen($parts[1]);
        if ($afterDot > 2) {
          // É separador de milhar, remove o ponto
          $v = str_replace('.', '', $v);
        }
        // Senão, mantém como está (formato americano)
      }
    }

    // Remove quaisquer caracteres não numéricos exceto ponto e sinal negativo
    $v = preg_replace('/[^0-9.\-]/', '', $v);

    // Converte para float
    $result = is_numeric($v) ? (float)$v : null;

    // Valida se o resultado é razoável (preço entre 0 e 1 milhão)
    if ($result !== null && ($result < 0 || $result > 1000000)) {
      \Log::warning("Preço fora do range esperado: original={$original}, convertido={$result}");
    }

    if ($result !== null) {
      \Log::debug("Conversão de preço: original='{$original}' -> limpo='{$v}' -> resultado={$result}");
    }

    return $result;
  }
}
