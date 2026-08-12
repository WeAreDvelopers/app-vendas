@extends('layouts.panel')
@section('title', 'Pedido ' . $order->ml_order_id)
@section('page-title', 'Pedido ' . $order->ml_order_id)
@section('page-subtitle', 'Detalhe da venda recebida do Mercado Livre')

@section('content')
<div class="mb-3 d-flex justify-content-between align-items-center">
  <a href="{{ route('panel.orders.index') }}" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i> Voltar
  </a>
  @if($order->label_url)
    <a target="_blank" href="{{ $order->label_url }}" class="btn btn-sm btn-outline-primary">
      <i class="bi bi-printer"></i> Etiqueta
    </a>
  @endif
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="notion-card mb-3">
      <h5 class="mb-3">Itens</h5>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr><th>Título</th><th>ML Item</th><th>Produto</th><th class="text-end">Qtd</th><th class="text-end">Preço</th><th class="text-end">Subtotal</th></tr>
          </thead>
          <tbody>
            @forelse($order->items as $it)
              <tr>
                <td>{{ $it->title }}</td>
                <td>{{ $it->ml_item_id ?? '—' }}</td>
                <td>
                  @if($it->product)
                    <a href="{{ route('panel.products.show', $it->product->id) }}">{{ $it->product->sku }}</a>
                  @else
                    <span class="muted">não vinculado</span>
                  @endif
                </td>
                <td class="text-end">{{ $it->qty }}</td>
                <td class="text-end">{{ number_format((float) $it->price, 2, ',', '.') }}</td>
                <td class="text-end">{{ number_format((float) $it->price * $it->qty, 2, ',', '.') }}</td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-muted">Sem itens neste pedido.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="notion-card">
      <details>
        <summary class="mb-2" style="cursor:pointer">Payload bruto (Mercado Livre)</summary>
        <pre class="mb-0" style="max-height:400px;overflow:auto"><code>{{ json_encode($order->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre>
      </details>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="notion-card mb-3">
      <h5 class="mb-3">Resumo</h5>
      <dl class="row mb-0">
        <dt class="col-5 muted">Status</dt>
        <dd class="col-7"><span class="chip">{{ $order->status }}</span></dd>

        <dt class="col-5 muted">Total</dt>
        <dd class="col-7">{{ $order->currency ?? 'BRL' }} {{ number_format((float) $order->total_amount, 2, ',', '.') }}</dd>

        <dt class="col-5 muted">Pago</dt>
        <dd class="col-7">{{ $order->currency ?? 'BRL' }} {{ number_format((float) $order->paid_amount, 2, ',', '.') }}</dd>

        <dt class="col-5 muted">Fechado em</dt>
        <dd class="col-7">{{ $order->date_closed ? \Illuminate\Support\Carbon::parse($order->date_closed)->format('d/m/Y H:i') : '—' }}</dd>

        <dt class="col-5 muted">Recebido em</dt>
        <dd class="col-7">{{ \Illuminate\Support\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</dd>
      </dl>
    </div>

    <div class="notion-card mb-3">
      <h5 class="mb-3">Comprador</h5>
      <dl class="row mb-0">
        <dt class="col-5 muted">Apelido</dt>
        <dd class="col-7">{{ $order->buyer_nickname ?? '—' }}</dd>
        <dt class="col-5 muted">ID ML</dt>
        <dd class="col-7">{{ $order->buyer_ml_id ?? '—' }}</dd>
      </dl>
    </div>

    <div class="notion-card">
      <h5 class="mb-3">Pagamento &amp; Envio</h5>
      <dl class="row mb-0">
        <dt class="col-5 muted">Pagamento</dt>
        <dd class="col-7">@if($order->payment_status)<span class="chip">{{ $order->payment_status }}</span>@else — @endif</dd>
        <dt class="col-5 muted">Envio</dt>
        <dd class="col-7">@if($order->shipping_status)<span class="chip">{{ $order->shipping_status }}</span>@else — @endif</dd>
        <dt class="col-5 muted">Envio ID</dt>
        <dd class="col-7">{{ $order->shipment_id ?? '—' }}</dd>
      </dl>
    </div>
  </div>
</div>
@endsection
