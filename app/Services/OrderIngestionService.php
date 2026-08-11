<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderIngestionService
{
    public function ingest(int $companyId, array $mlOrder, bool $notify = true): Order
    {
        $mlOrderId = (string) ($mlOrder['id'] ?? '');

        return DB::transaction(function () use ($companyId, $mlOrder, $mlOrderId, $notify) {
            $order = Order::withoutCompanyScope()
                ->firstOrNew(['company_id' => $companyId, 'ml_order_id' => $mlOrderId]);
            $isNew = !$order->exists;

            $order->company_id = $companyId;
            $order->ml_order_id = $mlOrderId;
            $order->status = (string) ($mlOrder['status'] ?? 'unknown');
            $order->total_amount = $mlOrder['total_amount'] ?? null;
            $order->paid_amount = $mlOrder['paid_amount'] ?? null;
            $order->currency = $mlOrder['currency_id'] ?? 'BRL';
            $order->buyer_ml_id = isset($mlOrder['buyer']['id']) ? (string) $mlOrder['buyer']['id'] : null;
            $order->buyer_nickname = $mlOrder['buyer']['nickname'] ?? null;
            $order->payment_status = $mlOrder['payments'][0]['status'] ?? null;
            $order->shipping_status = $mlOrder['shipping']['status'] ?? null;
            $order->shipment_id = isset($mlOrder['shipping']['id']) ? (string) $mlOrder['shipping']['id'] : null;
            $order->date_closed = $mlOrder['date_closed'] ?? null;
            $order->payload = $mlOrder;
            $order->save();

            foreach (($mlOrder['order_items'] ?? []) as $line) {
                $item = $line['item'] ?? [];
                $mlItemId = isset($item['id']) ? (string) $item['id'] : null;
                $sku = $item['seller_sku'] ?? $item['seller_custom_field'] ?? null;

                $productId = null;
                if ($sku) {
                    $productId = Product::withoutCompanyScope()
                        ->where('company_id', $companyId)->where('sku', $sku)->value('id');
                }

                $oi = OrderItem::withoutCompanyScope()->firstOrNew([
                    'order_id' => $order->id,
                    'ml_item_id' => $mlItemId,
                ]);
                $oi->company_id = $companyId;
                $oi->order_id = $order->id;
                $oi->ml_item_id = $mlItemId;
                $oi->product_id = $productId;
                $oi->title = $item['title'] ?? '';
                $oi->qty = (int) ($line['quantity'] ?? 1);
                $oi->price = $line['unit_price'] ?? 0;
                $oi->save();
            }

            if ($isNew && $notify) {
                $this->notify($companyId, $order);
            }

            return $order;
        });
    }

    private function notify(int $companyId, Order $order): void
    {
        // `notifications` é keyed por user_id (não tem company_id) e o worker não
        // tem usuário logado → notificar cada usuário da empresa.
        // NotificationHelper::success(title, message, actionUrl, actionText, userId).
        try {
            $company = \App\Models\Company::find($companyId);
            if (!$company) {
                return;
            }
            $message = "Pedido {$order->ml_order_id} — R$ " . number_format((float) $order->total_amount, 2, ',', '.');
            foreach ($company->users()->pluck('users.id') as $userId) {
                \App\Helpers\NotificationHelper::success(
                    'Nova venda no Mercado Livre',
                    $message,
                    '/panel/orders',
                    'Ver pedidos',
                    (int) $userId,
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('Falha ao notificar nova venda', ['error' => $e->getMessage()]);
        }
    }
}
