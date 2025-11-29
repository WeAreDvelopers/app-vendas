@extends('layouts.panel')
@section('title','Dashboard')
@section('page-title','Dashboard')
@section('page-subtitle','Visão geral do pipeline')

@section('content')
<div class="row g-3">
  <div class="col-md-3">
    <div class="notion-card">
      <div class="muted mb-1">Importações</div>
      <div class="fs-3 fw-bold">{{ number_format($stats['imports']) }}</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="notion-card">
      <div class="muted mb-1">Produtos</div>
      <div class="fs-3 fw-bold">{{ number_format($stats['products']) }}</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="notion-card">
      <div class="muted mb-1">Publicações</div>
      <div class="fs-3 fw-bold">{{ number_format($stats['listings']) }}</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="notion-card">
      <div class="muted mb-1">Pedidos</div>
      <div class="fs-3 fw-bold">{{ number_format($stats['orders']) }}</div>
    </div>
  </div>
</div>

<div class="notion-card mt-4">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div class="fw-semibold">Pedidos recentes</div>
    <a href="{{ route('panel.orders.index') }}" class="btn btn-sm btn-outline-secondary">Ver todos</a>
  </div>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>ID</th><th>ML Order</th><th>Status</th><th>Data</th></tr></thead>
      <tbody>
        @forelse($recentOrders as $o)
          <tr>
            <td>#{{ $o->id }}</td>
            <td>{{ $o->ml_order_id }}</td>
            <td><span class="chip">{{ $o->status }}</span></td>
            <td>{{ \Illuminate\Support\Carbon::parse($o->created_at)->format('d/m/Y H:i') }}</td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-muted">Sem pedidos ainda.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
