@extends('layouts.panel')
@section('title','Dashboard')
@section('page-title','Dashboard')
@section('page-subtitle','Visão geral do pipeline')

@push('head')
<style>
  .stat-card { display:flex; align-items:center; gap:14px; }
  .stat-ico { width:46px; height:46px; border-radius:12px; display:grid; place-items:center; font-size:1.25rem; flex-shrink:0; }
  .stat-ico.blue  { background:var(--app-accent-soft); color:var(--app-accent); }
  .stat-ico.green { background:var(--app-green-soft);  color:var(--app-green); }
  .stat-ico.amber { background:var(--app-amber-soft);  color:var(--app-amber); }
  .stat-ico.slate { background:var(--app-surface-2);   color:var(--app-muted); }
  .stat-num { font-size:1.6rem; font-weight:700; line-height:1; font-variant-numeric:tabular-nums; letter-spacing:-.02em; }
  .stat-lbl { color:var(--app-muted); font-size:.82rem; font-weight:500; }
  .st { display:inline-flex; align-items:center; gap:5px; font-size:.72rem; font-weight:600; padding:3px 9px; border-radius:20px; }
  .st.g { color:var(--app-green);  background:var(--app-green-soft); }
  .st.b { color:var(--app-accent); background:var(--app-accent-soft); }
  .st.a { color:var(--app-amber);  background:var(--app-amber-soft); }
  .st.n { color:var(--app-muted);  background:var(--app-surface-2); }
</style>
@endpush

@section('content')
@php
  $stCls = ['paid'=>'g','approved'=>'g','delivered'=>'g','shipped'=>'b','ready_to_print'=>'n','cancelled'=>'a','canceled'=>'a'];
@endphp

<div class="row g-3">
  <div class="col-6 col-xl-3">
    <div class="notion-card stat-card">
      <span class="stat-ico slate"><i class="bi bi-upload"></i></span>
      <div><div class="stat-num">{{ number_format($stats['imports']) }}</div><div class="stat-lbl">Importações</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="notion-card stat-card">
      <span class="stat-ico blue"><i class="bi bi-box-seam"></i></span>
      <div><div class="stat-num">{{ number_format($stats['products']) }}</div><div class="stat-lbl">Produtos</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="notion-card stat-card">
      <span class="stat-ico amber"><i class="bi bi-megaphone"></i></span>
      <div><div class="stat-num">{{ number_format($stats['listings']) }}</div><div class="stat-lbl">Publicações</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="notion-card stat-card">
      <span class="stat-ico green"><i class="bi bi-receipt"></i></span>
      <div><div class="stat-num">{{ number_format($stats['orders']) }}</div><div class="stat-lbl">Pedidos</div></div>
    </div>
  </div>
</div>

<div class="notion-card mt-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div class="fw-semibold">Pedidos recentes</div>
    <a href="{{ route('panel.orders.index') }}" class="btn btn-sm btn-outline-secondary">Ver todos</a>
  </div>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th>#</th><th>ML Order</th><th>Status</th><th class="text-end">Quando</th></tr></thead>
      <tbody>
        @forelse($recentOrders as $o)
          <tr>
            <td class="tabular text-muted">{{ $o->id }}</td>
            <td class="fw-medium"><a href="{{ route('panel.orders.show', $o->id) }}" class="text-decoration-none">{{ $o->ml_order_id }}</a></td>
            <td><span class="st {{ $stCls[$o->status] ?? 'n' }}">{{ $o->status }}</span></td>
            <td class="text-end text-muted tabular">{{ \Illuminate\Support\Carbon::parse($o->created_at)->format('d/m/Y H:i') }}</td>
          </tr>
        @empty
          <tr><td colspan="4">
            <div class="text-center py-4 muted">
              <i class="bi bi-receipt fs-3 d-block mb-2 opacity-50"></i>
              Nenhuma venda ainda. Elas aparecem aqui automaticamente quando o Mercado Livre notificar.
            </div>
          </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
