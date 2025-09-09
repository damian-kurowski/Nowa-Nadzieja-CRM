<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DevToolsController extends AbstractController
{
    public function chromeDevTools(): Response
    {
        // Return empty 204 No Content response to Chrome DevTools requests
        // Add cache headers to reduce frequency of these requests
        $response = new Response('', Response::HTTP_NO_CONTENT);
        $response->headers->set('Cache-Control', 'public, max-age=3600'); // Cache for 1 hour
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));
        
        return $response;
    }
}