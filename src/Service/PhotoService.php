<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class PhotoService
{
    private string $targetDirectory;
    private SluggerInterface $slugger;

    public function __construct(string $photoDirectory, SluggerInterface $slugger)
    {
        $this->targetDirectory = $photoDirectory;
        $this->slugger = $slugger;
    }

    public function upload(UploadedFile $file, ?string $currentPhoto = null): string
    {
        // Sprawdź rzeczywisty MIME type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (false === $finfo) {
            throw new \RuntimeException('Nie można zainicjalizować finfo');
        }
        $realMimeType = finfo_file($finfo, $file->getPathname());
        finfo_close($finfo);

        if (false === $realMimeType) {
            throw new \InvalidArgumentException('Nie można określić typu MIME pliku');
        }

        if (!in_array($realMimeType, $allowedMimes)) {
            throw new \InvalidArgumentException('Nieprawidłowy typ pliku: '.$realMimeType);
        }

        // Sprawdź nagłówki pliku
        $imageInfo = getimagesize($file->getPathname());
        if (!$imageInfo) {
            throw new \InvalidArgumentException('Plik nie jest prawidłowym obrazem');
        }

        // Sprawdź rozmiar pliku (dodatkowo do walidacji formularza)
        if ($file->getSize() > 5 * 1024 * 1024) { // 5MB
            throw new \InvalidArgumentException('Plik jest zbyt duży');
        }

        // Usuń stare zdjęcie jeśli istnieje
        if ($currentPhoto) {
            $this->deletePhoto($currentPhoto);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        // Sanityzuj nazwę pliku bardziej agresywnie
        $safeFilename = preg_replace('/[^a-zA-Z0-9-_]/', '', $this->slugger->slug($originalFilename));
        $fileName = $safeFilename.'-'.uniqid().'.'.$this->getSecureExtension($realMimeType);

        // Przenieś plik do katalogu
        $file->move($this->getTargetDirectory(), $fileName);

        // Przytnij zdjęcie do 500x500px
        $this->cropImage($this->getTargetDirectory().'/'.$fileName);

        return $fileName;
    }

    public function cropImage(string $filePath): void
    {
        // Sprawdź czy plik istnieje
        if (!file_exists($filePath)) {
            return;
        }

        // Sprawdź czy rozszerzenie GD jest dostępne
        if (!extension_loaded('gd')) {
            // Jeśli GD nie jest dostępne, po prostu pozostaw plik bez przetwarzania
            return;
        }

        // Pobierz informacje o obrazie
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            return;
        }

        [$width, $height, $type] = $imageInfo;

        // Utwórz zasób obrazu w zależności od typu
        $sourceImage = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                if (function_exists('imagecreatefromjpeg')) {
                    $sourceImage = imagecreatefromjpeg($filePath);
                }
                break;
            case IMAGETYPE_PNG:
                if (function_exists('imagecreatefrompng')) {
                    $sourceImage = imagecreatefrompng($filePath);
                }
                break;
            case IMAGETYPE_GIF:
                if (function_exists('imagecreatefromgif')) {
                    $sourceImage = imagecreatefromgif($filePath);
                }
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $sourceImage = imagecreatefromwebp($filePath);
                }
                break;
            default:
                return;
        }

        if (!$sourceImage) {
            return;
        }

        // Oblicz wymiary dla przycinania (kwadrat ze środka obrazu)
        $targetSize = 500;
        $cropSize = min($width, $height);
        $cropX = ($width - $cropSize) / 2;
        $cropY = ($height - $cropSize) / 2;

        // Sprawdź czy funkcje GD są dostępne
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled')) {
            imagedestroy($sourceImage);

            return;
        }

        // Utwórz nowy obraz 500x500
        $destinationImage = imagecreatetruecolor($targetSize, $targetSize);

        if (!$destinationImage) {
            imagedestroy($sourceImage);

            return;
        }

        // Zachowaj przezroczystość dla PNG i GIF
        if (IMAGETYPE_PNG == $type || IMAGETYPE_GIF == $type) {
            if (function_exists('imagecolortransparent') && function_exists('imagecolorallocatealpha')) {
                $transparentColor = imagecolorallocatealpha($destinationImage, 0, 0, 0, 127);
                if (false !== $transparentColor) {
                    imagecolortransparent($destinationImage, $transparentColor);
                }
                imagealphablending($destinationImage, false);
                imagesavealpha($destinationImage, true);
            }
        }

        // Przytnij i przeskaluj obraz
        imagecopyresampled(
            $destinationImage,
            $sourceImage,
            0, 0,
            $cropX, $cropY,
            $targetSize, $targetSize,
            $cropSize, $cropSize
        );

        // Zapisz przetworzony obraz
        switch ($type) {
            case IMAGETYPE_JPEG:
                if (function_exists('imagejpeg')) {
                    imagejpeg($destinationImage, $filePath, 90);
                }
                break;
            case IMAGETYPE_PNG:
                if (function_exists('imagepng')) {
                    imagepng($destinationImage, $filePath);
                }
                break;
            case IMAGETYPE_GIF:
                if (function_exists('imagegif')) {
                    imagegif($destinationImage, $filePath);
                }
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    imagewebp($destinationImage, $filePath, 90);
                }
                break;
        }

        // Zwolnij pamięć
        if (function_exists('imagedestroy')) {
            imagedestroy($sourceImage);
            imagedestroy($destinationImage);
        }
    }

    public function deletePhoto(string $fileName): void
    {
        $filePath = $this->getTargetDirectory().'/'.$fileName;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    private function getSecureExtension(string $mimeType): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        return $extensions[$mimeType] ?? 'jpg';
    }
}
