@extends('layouts.panel')
@section('title','Pedidos')
@section('page-title','Pedidos')
@section('page-subtitle','Vendas recebidas via webhook do Mercado Livre')

@section('content')
<div class="notion-card">
  <form class="row g-2 mb-3" method="get">
    <div class="col-md-4">
      <input type="text" name="q" value="{{ request('q') }}" class="form-control"
             placeholder="Buscar por nº do pedido ou comprador">
    </div>
    <div class="col-md-3">
      <select name="status" class="form-select" onchange="this.form.submit()">
        <option value="">Todos status</option>
        @foreach($statuses as $st)
          <option value="{{ $st }}" @selected(request('status')===$st)>{{ ucfirst($st) }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary w-100">Filtrar</button>
    </div>
    <div class="col-md-2">
      <a href="{{ route('panel.orders.index') }}" class="btn btn-outline-secondary w-100">Limpar</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>#</th><th>ML Order</th><th>Comprador</th><th>Itens</th><th>Total</th>
          <th>Pagamento</th><th>Envio</th><th>Status</th><th>Etiqueta</th><th>Quando</th>
        </tr>
      </thead>
      <tbody>
        @forelse($orders as $o)
          <tr>
            <td>{{ $o->id }}</td>
            <td><a href="{{ route('panel.orders.show', $o->id) }}">{{ $o->ml_order_id }}</a></td>
            <td>{{ $o->buyer_nickname ?? '—' }}</td>
            <td>{{ $o->items_count }}</td>
            <td>{{ $o->currency ?? 'BRL' }} {{ number_format((float) $o->total_amount, 2, ',', '.') }}</td>
            <td>@if($o->payment_status)<span class="chip">{{ $o->payment_status }}</span>@else<span class="muted">—</span>@endif</td>
            <td>@if($o->shipping_status)<span class="chip">{{ $o->shipping_status }}</span>@else<span class="muted">—</span>@endif</td>
            <td><span class="chip">{{ $o->status }}</span></td>
            <td>
              @if($o->label_url)
                <a target="_blank" href="{{ $o->label_url }}" class="btn btn-sm btn-outline-secondary">Abrir</a>
              @else
                <span class="muted">—</span>
              @endif
            </td>
            <td>{{ \Illuminate\Support\Carbon::parse($o->created_at)->format('d/m/Y H:i') }}</td>
          </tr>
        @empty
          <tr><td colspan="10" class="text-muted">Sem pedidos cadastrados.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $orders->links() }}
</div>
@endsection
