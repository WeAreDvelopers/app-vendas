@extends('layouts.panel')
@section('title','Publicações')
@section('page-title','Publicações no Mercado Livre')
@section('page-subtitle','Acompanhe status e erros de publicação')

@section('content')
<div class="notion-card">
  <form class="row g-2 mb-3" method="get">
    <div class="col-md-3">
      <select name="status" class="form-select" onchange="this.form.submit()">
        <option value="">Todos status</option>
        @foreach($statuses as $st)
          <option value="{{ $st }}" @selected($status===$st)>{{ ucfirst($st) }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <a href="{{ route('panel.listings.index') }}" class="btn btn-outline-secondary w-100">Limpar</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>#</th><th>SKU</th><th>Produto</th><th>Título</th><th>Preço</th><th>Estoque</th><th>Status</th><th>ML Item</th><th>Erro</th></tr></thead>
      <tbody>
        @forelse($listings as $l)
          <tr>
            <td>{{ $l->id }}</td>
            <td>{{ $l->sku }}</td>
            <td>{{ $l->product_name }}</td>
            <td>{{ $l->title }}</td>
            <td>{{ $l->price }}</td>
            <td>{{ $l->stock }}</td>
            <td><span class="chip">{{ $l->status }}</span></td>
            <td>{{ $l->ml_item_id }}</td>
            <td class="text-danger" style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $l->last_error }}</td>
          </tr>
        @empty
          <tr><td colspan="9" class="text-muted">Nenhuma publicação.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $listings->links() }}
</div>
@endsection
