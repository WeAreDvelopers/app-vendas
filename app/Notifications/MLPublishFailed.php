<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MLPublishFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public object $product,
        public string $errorMessage
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('Erro ao publicar no Mercado Livre')
            ->greeting('Falha na Publicação')
            ->line('Houve um erro ao publicar seu produto no Mercado Livre.')
            ->line('**Produto:** ' . $this->product->name)
            ->line('**Erro:** ' . $this->errorMessage)
            ->line('Por favor, verifique as informações do produto e tente novamente.')
            ->action('Ver Produto', url('/panel/products/' . $this->product->id));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'ml_publish_failed',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'error' => $this->errorMessage,
            'message' => 'Erro ao publicar produto no Mercado Livre',
        ];
    }
}
