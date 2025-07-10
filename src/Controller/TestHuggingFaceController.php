<?php

namespace App\Controller;

use App\Service\HuggingFaceImageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestHuggingFaceController extends AbstractController
{
    #[Route('/test-huggingface-image', name: 'test_huggingface_image')]
    public function testImageGeneration(HuggingFaceImageService $imageService, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $prompt = $request->request->get('prompt', 'A modern business office');
            $model = $request->request->get('model', 'stable-diffusion-xl');
            
            $result = $imageService->generateImage($prompt, $model);
            
            if ($result['success']) {
                $imagePath = $imageService->saveBase64Image(
                    $result['image_data'],
                    $result['mime_type']
                );
                
                return $this->render('test/huggingface_result.html.twig', [
                    'success' => true,
                    'image_url' => '/' . $imagePath,
                    'prompt' => $prompt,
                    'model' => $model,
                    'model_used' => $result['model_used']
                ]);
            }
            
            return $this->render('test/huggingface_result.html.twig', [
                'success' => false,
                'error' => $result['error'],
                'retry_after' => $result['retry_after'] ?? null,
                'prompt' => $prompt,
                'model' => $model
            ]);
        }
        
        return $this->render('test/huggingface_form.html.twig', [
            'available_models' => $imageService->listAvailableModels()
        ]);
    }

    #[Route('/test-huggingface-landing', name: 'test_huggingface_landing')]
    public function testLandingPageImages(HuggingFaceImageService $imageService, Request $request): JsonResponse
    {
        $businessPrompt = $request->query->get('business', 'modern technology startup');
        
        $results = $imageService->generateLandingPageImages($businessPrompt);
        
        return new JsonResponse([
            'business_prompt' => $businessPrompt,
            'images_generated' => $results,
            'summary' => [
                'total_images' => count($results),
                'successful' => count(array_filter($results, fn($r) => $r['success'])),
                'failed' => count(array_filter($results, fn($r) => !$r['success']))
            ]
        ]);
    }
}
