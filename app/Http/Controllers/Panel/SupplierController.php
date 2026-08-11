<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierMapping;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::where('company_id', auth()->user()->current_company_id)
            ->withCount('imports')
            ->latest()
            ->paginate(20);
        return view('panel.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('panel.suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:suppliers',
            'code' => 'required|string|max:255|unique:suppliers',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $validated['company_id'] = auth()->user()->current_company_id;

        $supplier = Supplier::create($validated);

        return redirect()->route('panel.suppliers.edit', $supplier)
            ->with('success', 'Fornecedor criado com sucesso!');
    }

    public function edit(Supplier $supplier)
    {
        $supplier->load('mapping');
        return view('panel.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:suppliers,name,' . $supplier->id,
            'code' => 'required|string|max:255|unique:suppliers,code,' . $supplier->id,
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $supplier->update($validated);

        return redirect()->route('panel.suppliers.edit', $supplier)
            ->with('success', 'Fornecedor atualizado com sucesso!');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('panel.suppliers.index')
            ->with('success', 'Fornecedor removido com sucesso!');
    }

    public function editMapping(Supplier $supplier)
    {
        $supplier->load('mapping');

        // Campos padrão que podem ser mapeados
        $defaultFields = [
            'sku' => ['label' => 'SKU / Código', 'examples' => ['sku', 'codigo', 'cod', 'product_code']],
            'ean' => ['label' => 'EAN / GTIN', 'examples' => ['ean', 'ean13', 'gtin', 'barcode']],
            'name' => ['label' => 'Nome do Produto', 'examples' => ['name', 'descricao', 'descrição', 'produto', 'title']],
            'brand' => ['label' => 'Marca', 'examples' => ['brand', 'marca', 'fabricante']],
            'cost_price' => ['label' => 'Preço de Custo', 'examples' => ['cost', 'custo', 'preco_custo', 'preço_custo']],
            'sale_price' => ['label' => 'Preço de Venda', 'examples' => ['price', 'preco', 'preço', 'preco_venda', 'preço_venda']],
            'stock' => ['label' => 'Estoque', 'examples' => ['stock', 'estoque', 'quantidade', 'qty']],
            'weight' => ['label' => 'Peso (g)', 'examples' => ['weight', 'peso', 'weight_g']],
            'width' => ['label' => 'Largura (cm)', 'examples' => ['width', 'largura']],
            'height' => ['label' => 'Altura (cm)', 'examples' => ['height', 'altura']],
            'length' => ['label' => 'Comprimento (cm)', 'examples' => ['length', 'comprimento', 'profundidade']],
        ];

        return view('panel.suppliers.mapping', compact('supplier', 'defaultFields'));
    }

    public function updateMapping(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'mappings' => 'required|array',
            'mappings.*' => 'nullable|string',
        ]);

        // Remove campos vazios
        $mappings = array_filter($validated['mappings'], fn($v) => !empty($v));

        // Converte cada campo em array de possíveis valores (separados por vírgula)
        $columnMappings = [];
        foreach ($mappings as $field => $value) {
            $columnMappings[$field] = array_map('trim', explode(',', $value));
        }

        if ($supplier->mapping) {
            $supplier->mapping->update(['column_mappings' => $columnMappings]);
        } else {
            SupplierMapping::create([
                'supplier_id' => $supplier->id,
                'column_mappings' => $columnMappings,
            ]);
        }

        return redirect()->route('panel.suppliers.edit', $supplier)
            ->with('success', 'Mapeamento atualizado com sucesso!');
    }
}
