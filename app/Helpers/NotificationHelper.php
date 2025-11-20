<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class NotificationHelper
{
    /**
     * Envia uma notificação de sucesso
     */
    public static function success(string $title, string $message, ?string $actionUrl = null, ?string $actionText = null, ?int $userId = null)
    {
        return self::create([
            'type' => 'success',
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'action_text' => $actionText,
            'user_id' => $userId,
        ]);
    }

    /**
     * Envia uma notificação de informação
     */
    public static function info(string $title, string $message, ?string $actionUrl = null, ?string $actionText = null, ?int $userId = null)
    {
        return self::create([
            'type' => 'info',
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'action_text' => $actionText,
            'user_id' => $userId,
        ]);
    }

    /**
     * Envia uma notificação de aviso
     */
    public static function warning(string $title, string $message, ?string $actionUrl = null, ?string $actionText = null, ?int $userId = null)
    {
        return self::create([
            'type' => 'warning',
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'action_text' => $actionText,
            'user_id' => $userId,
        ]);
    }

    /**
     * Envia uma notificação de erro
     */
    public static function error(string $title, string $message, ?string $actionUrl = null, ?string $actionText = null, ?int $userId = null)
    {
        return self::create([
            'type' => 'error',
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'action_text' => $actionText,
            'user_id' => $userId,
        ]);
    }

    /**
     * Envia uma notificação customizada
     */
    public static function create(array $data)
    {
        return DB::table('notifications')->insertGetId([
            'user_id' => $data['user_id'] ?? null,
            'type' => $data['type'] ?? 'info',
            'title' => $data['title'],
            'message' => $data['message'],
            'icon' => $data['icon'] ?? null,
            'action_url' => $data['action_url'] ?? null,
            'action_text' => $data['action_text'] ?? null,
            'read' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Notificação para importação concluída
     */
    public static function importCompleted(int $importId, int $totalRows, int $processedRows, ?int $userId = null)
    {
        return self::success(
            'Importação Concluída',
            "Importação #{$importId} finalizada! {$processedRows} de {$totalRows} produtos processados.",
            "/panel/imports/{$importId}",
            'Ver Detalhes',
            $userId
        );
    }

    /**
     * Notificação para importação com erros
     */
    public static function importWithErrors(int $importId, int $errorCount, ?int $userId = null)
    {
        return self::warning(
            'Importação com Erros',
            "Importação #{$importId} concluída com {$errorCount} erro(s). Verifique os detalhes.",
            "/panel/imports/{$importId}/errors",
            'Ver Erros',
            $userId
        );
    }

    /**
     * Notificação para processamento de IA concluído
     */
    public static function aiProcessingCompleted(int $productCount, ?int $userId = null)
    {
        return self::success(
            'Processamento IA Concluído',
            "{$productCount} produto(s) processado(s) com IA. Descrições e imagens foram geradas!",
            '/panel/products',
            'Ver Produtos',
            $userId
        );
    }

    /**
     * Notificação para produto publicado no ML
     */
    public static function productPublished(int $productId, string $productName, ?int $userId = null)
    {
        return self::success(
            'Produto Publicado',
            "'{$productName}' foi publicado com sucesso no Mercado Livre!",
            "/panel/products/{$productId}",
            'Ver Produto',
            $userId
        );
    }

    /**
     * Notificação para erro na publicação
     */
    public static function publishError(string $productName, string $error, ?int $userId = null)
    {
        return self::error(
            'Erro na Publicação',
            "Falha ao publicar '{$productName}': {$error}",
            null,
            null,
            $userId
        );
    }
}
