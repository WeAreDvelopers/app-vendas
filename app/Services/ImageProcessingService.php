<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;

class ImageProcessingService
{
    protected $manager;

    public function __construct()
    {
        // Usa GD driver (mais compatível)
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Processa imagem fazendo upload: redimensiona mantendo proporção,
     * adiciona fundo branco e garante mínimo de 500x500px
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int $productId
     * @param int $minWidth Largura mínima (padrão: 500)
     * @param int $minHeight Altura mínima (padrão: 500)
     * @return array ['path' => string, 'width' => int, 'height' => int]
     */
    public function processAndSaveProductImage($file, int $productId, int $minWidth = 500, int $minHeight = 500): array
    {
        // Carrega a imagem
        $image = $this->manager->read($file->getRealPath());

        // Dimensões originais
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        // Calcula dimensões finais mantendo proporção
        $finalWidth = max($originalWidth, $minWidth);
        $finalHeight = max($originalHeight, $minHeight);

        // Se a imagem for menor que o mínimo, usa o mínimo
        if ($originalWidth < $minWidth || $originalHeight < $minHeight) {
            $ratio = $originalWidth / $originalHeight;

            if ($ratio > 1) {
                // Paisagem
                $finalWidth = max($minWidth, $originalWidth);
                $finalHeight = (int)($finalWidth / $ratio);
            } else {
                // Retrato ou quadrado
                $finalHeight = max($minHeight, $originalHeight);
                $finalWidth = (int)($finalHeight * $ratio);
            }

            // Redimensiona mantendo proporção
            $image->scale(width: $finalWidth, height: $finalHeight);
        }

        // Cria canvas com fundo branco
        $canvas = $this->manager->create($finalWidth, $finalHeight)->fill('#ffffff');

        // Centraliza a imagem no canvas
        $x = (int)(($finalWidth - $image->width()) / 2);
        $y = (int)(($finalHeight - $image->height()) / 2);

        // Coloca a imagem no canvas
        $canvas->place($image, 'top-left', $x, $y);

        // Gera nome único para o arquivo
        $filename = uniqid('product_' . $productId . '_') . '.jpg';
        $path = 'product_images/' . $filename;

        // Salva no storage público como JPEG (melhor compressão)
        $canvas->toJpeg(quality: 90)->save(storage_path('app/public/' . $path));

        return [
            'path' => '/storage/' . $path,
            'width' => $finalWidth,
            'height' => $finalHeight
        ];
    }

    /**
     * Redimensiona imagem mantendo proporção e adicionando fundo branco
     *
     * @param string $imagePath Caminho da imagem no storage
     * @param int $width Largura desejada
     * @param int $height Altura desejada
     * @return string Novo caminho da imagem processada
     */
    public function resizeWithWhiteBackground(string $imagePath, int $width = 500, int $height = 500): string
    {
        // Remove /storage/ do path para acessar no disco público
        $storagePath = str_replace('/storage/', '', $imagePath);
        $fullPath = storage_path('app/public/' . $storagePath);

        if (!file_exists($fullPath)) {
            throw new \Exception("Imagem não encontrada: {$fullPath}");
        }

        // Carrega a imagem
        $image = $this->manager->read($fullPath);

        // Redimensiona mantendo proporção
        $image->scale(width: $width, height: $height);

        // Cria canvas com fundo branco
        $canvas = $this->manager->create($width, $height)->fill('#ffffff');

        // Centraliza a imagem no canvas
        $x = (int)(($width - $image->width()) / 2);
        $y = (int)(($height - $image->height()) / 2);

        // Coloca a imagem no canvas
        $canvas->place($image, 'top-left', $x, $y);

        // Gera novo nome
        $filename = uniqid('resized_') . '.jpg';
        $newPath = 'product_images/' . $filename;

        // Salva no storage público
        $canvas->toJpeg(quality: 90)->save(storage_path('app/public/' . $newPath));

        return '/storage/' . $newPath;
    }

    /**
     * Cria thumbnail quadrado com fundo branco
     *
     * @param string $imagePath
     * @param int $size
     * @return string
     */
    public function createThumbnail(string $imagePath, int $size = 200): string
    {
        $storagePath = str_replace('/storage/', '', $imagePath);
        $fullPath = storage_path('app/public/' . $storagePath);

        if (!file_exists($fullPath)) {
            throw new \Exception("Imagem não encontrada: {$fullPath}");
        }

        // Carrega a imagem
        $image = $this->manager->read($fullPath);

        // Redimensiona mantendo proporção para caber no quadrado
        $image->scale(width: $size, height: $size);

        // Cria canvas quadrado com fundo branco
        $canvas = $this->manager->create($size, $size)->fill('#ffffff');

        // Centraliza a imagem
        $x = (int)(($size - $image->width()) / 2);
        $y = (int)(($size - $image->height()) / 2);

        $canvas->place($image, 'top-left', $x, $y);

        // Gera nome
        $filename = uniqid('thumb_') . '.jpg';
        $path = 'product_images/thumbs/' . $filename;

        // Cria diretório se não existir
        if (!file_exists(storage_path('app/public/product_images/thumbs'))) {
            mkdir(storage_path('app/public/product_images/thumbs'), 0755, true);
        }

        // Salva
        $canvas->toJpeg(quality: 85)->save(storage_path('app/public/' . $path));

        return '/storage/' . $path;
    }
}
