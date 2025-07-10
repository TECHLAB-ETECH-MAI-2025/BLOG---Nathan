<?php

namespace App\Controller;

use App\Service\GeminiImageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TestGeminiImageController extends AbstractController
{
    #[Route('/test-gemini-image', name: 'test_gemini_image')]
    public function testImageGeneration(GeminiImageService $imageService, Request $request): JsonResponse
    {
        $prompt = $request->query->get('prompt', 'A modern business office with computers and plants');
        
        $result = $imageService->generateImage($prompt);
        
        if ($result['success'] && $result['image_type'] === 'base64') {
            // Sauvegarder l'image
            $imagePath = $imageService->saveBase64Image(
                $result['image_data'],
                $result['mime_type']
            );
            
            return new JsonResponse([
                'success' => true,
                'image_url' => $imagePath,
                'message' => 'Image générée avec succès'
            ]);
        }
        
        return new JsonResponse($result);
    }
}
