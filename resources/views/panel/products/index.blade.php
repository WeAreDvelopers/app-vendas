@extends('layouts.panel')
@section('title','Produtos')
@section('page-title','Produtos')
@section('page-subtitle','Catálogo normalizado pronto para publicação')

@section('content')
<div class="notion-card">
  <form class="row g-2 mb-3" method="get">
    <div class="col-md-6">
      <input type="search" class="form-control" name="q" value="{{ $search }}" placeholder="Buscar por nome, SKU ou EAN">
    </div>
    <div class="col-md-2">
      <button class="btn btn-dark w-100">Buscar</button>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>#</th><th>SKU</th><th>EAN</th><th>Nome</th><th>Preço</th><th>Estoque</th><th>Status</th><th>Ações</th></tr></thead>
      <tbody>
        @forelse($products as $p)
          <tr>
            <td>{{ $p->id }}</td>
            <td class="fw-semibold">{{ $p->sku }}</td>
            <td>{{ $p->ean ?? '-' }}</td>
            <td>
              <div class="fw-semibold">{{ $p->name }}</div>
              @if($p->description)
                <small class="text-success">
                  <i class="bi bi-magic"></i> Com descrição IA
                </small>
              @endif
            </td>
            <td>{{ $p->price ? 'R$ ' . number_format($p->price, 2, ',', '.') : '-' }}</td>
            <td>{{ $p->stock }}</td>
            <td>
              <span class="chip chip-{{ $p->status === 'ready' ? 'success' : 'secondary' }}">
                {{ $p->status }}
              </span>
            </td>
            <td>
              <a href="{{ route('panel.products.show', $p->id) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-eye"></i> Ver
              </a>
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-muted">Nenhum produto encontrado.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $products->links() }}
</div>
@endsection
