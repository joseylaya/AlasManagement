<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class ProductImageStorageService
{
    public function upload(UploadedFile $image, string $productSlug): array
    {
        [$contents, $width, $height] = $this->toWebp($image);
        $path = trim(Str::slug($productSlug) ?: 'product', '/').'/'.Str::uuid().'.webp';
        $response = $this->request()
            ->withHeaders(['Content-Type' => 'image/webp', 'x-upsert' => 'false'])
            ->send('POST', $this->url().'/storage/v1/object/'.$this->bucket().'/'.$path, ['body' => $contents]);

        if (! $response->successful()) {
            throw new RuntimeException('Supabase Storage upload failed: '.$response->body());
        }

        return [
            'url' => $this->url().'/storage/v1/object/public/'.$this->bucket().'/'.$path,
            'path' => $path,
            'width' => $width,
            'height' => $height,
        ];
    }

    public function ensureBucket(): void
    {
        $response = $this->request()->post($this->url().'/storage/v1/bucket', [
            'id' => $this->bucket(),
            'name' => $this->bucket(),
            'public' => true,
            'file_size_limit' => 8 * 1024 * 1024,
            'allowed_mime_types' => ['image/webp'],
        ]);

        if (! $response->successful() && $response->status() !== 409 && ! str_contains(strtolower($response->body()), 'already exists')) {
            throw new RuntimeException('Supabase Storage bucket setup failed: '.$response->body());
        }
    }

    public function deleteByPublicUrl(string $publicUrl): void
    {
        $marker = '/storage/v1/object/public/'.$this->bucket().'/';
        $position = strpos($publicUrl, $marker);
        if ($position === false) {
            return;
        }

        $path = rawurldecode(substr($publicUrl, $position + strlen($marker)));
        $this->request()->delete($this->url().'/storage/v1/object/'.$this->bucket(), ['prefixes' => [$path]])->throw();
    }

    private function toWebp(UploadedFile $image): array
    {
        $source = match ($image->getMimeType()) {
            'image/jpeg' => imagecreatefromjpeg($image->getRealPath()),
            'image/png' => imagecreatefrompng($image->getRealPath()),
            'image/webp' => imagecreatefromwebp($image->getRealPath()),
            default => false,
        };

        if (! $source) {
            throw new RuntimeException('The selected image could not be processed.');
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, 1800 / max($sourceWidth, $sourceHeight));
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

        ob_start();
        imagewebp($canvas, null, 82);
        $contents = ob_get_clean();
        imagedestroy($source);
        imagedestroy($canvas);

        if (! is_string($contents) || $contents === '') {
            throw new RuntimeException('The WebP image could not be generated.');
        }

        return [$contents, $width, $height];
    }

    private function url(): string
    {
        return rtrim((string) config('services.supabase.url'), '/');
    }

    private function key(): string
    {
        return (string) config('services.supabase.service_role_key');
    }

    private function request()
    {
        if ($this->url() === '' || $this->key() === '') {
            throw new RuntimeException('Supabase Storage credentials are not configured.');
        }

        return Http::withToken($this->key())->withHeaders(['apikey' => $this->key()])->timeout(30);
    }

    private function bucket(): string
    {
        return (string) config('services.supabase.product_images_bucket', 'product-images');
    }
}
