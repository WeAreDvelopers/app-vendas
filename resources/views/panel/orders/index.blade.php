@extends('layouts.panel')
@section('title','Pedidos')
@section('page-title','Pedidos')
@section('page-subtitle','Pedidos recebidos via webhook do Mercado Livre')

@section('content')
<div class="notion-card">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>#</th><th>ML Order</th><th>Status</th><th>Etiqueta</th><th>Quando</th></tr></thead>
      <tbody>
        @forelse($orders as $o)
          <tr>
            <td>{{ $o->id }}</td>
            <td>{{ $o->ml_order_id }}</td>
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
          <tr><td colspan="5" class="text-muted">Sem pedidos cadastrados.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $orders->links() }}
</div>
@endsection
