<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MLPublishCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public object $product,
        public array $result
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mlId = $this->result['id'] ?? 'N/A';
        $permalink = $this->result['permalink'] ?? '#';

        return (new MailMessage)
            ->success()
            ->subject('Anúncio publicado no Mercado Livre')
            ->greeting('Anúncio Publicado!')
            ->line('Seu produto foi publicado com sucesso no Mercado Livre.')
            ->line('**Produto:** ' . $this->product->name)
            ->line('**ID do Anúncio:** ' . $mlId)
            ->action('Ver no Mercado Livre', $permalink)
            ->line('Obrigado por usar nossa plataforma!');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'ml_publish_completed',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'ml_id' => $this->result['id'] ?? null,
            'permalink' => $this->result['permalink'] ?? null,
            'message' => 'Produto publicado com sucesso no Mercado Livre',
        ];
    }
}
