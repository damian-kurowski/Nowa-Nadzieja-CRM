<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    #[Route('/uploads/photos/{filename}', name: 'image_show', methods: ['GET'])]
    public function show(string $filename): Response
    {
        /** @var string $uploadDir */
        $uploadDir = $this->getParameter('photos_directory');
        $filePath = $uploadDir.'/'.$filename;

        // Security check - prevent directory traversal
        $realDirPath = realpath(dirname($filePath));
        $realUploadPath = realpath($uploadDir);
        if (!$realDirPath || !$realUploadPath || !str_starts_with($realDirPath, $realUploadPath)) {
            throw new NotFoundHttpException('Plik nie został znaleziony');
        }

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('Plik nie został znaleziony');
        }

        // Additional security - check file type
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            throw new NotFoundHttpException('Nieprawidłowy typ pliku');
        }

        $response = new BinaryFileResponse($filePath);

        // Set proper MIME type
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];

        $response->headers->set('Content-Type', $mimeTypes[$extension]);

        // Cache headers for better performance
        $response->setSharedMaxAge(3600); // 1 hour
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }
}
