<?php

namespace App\Controller;

use App\Service\HuggingFaceVideoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestVideoController extends AbstractController
{
    #[Route('/debug-video-models', name: 'debug_video_models')]
    public function debugVideoModels(HuggingFaceVideoService $videoService): JsonResponse
    {
        $models = $videoService->listAvailableModels();
        $results = [];
        
        foreach ($models as $key => $modelId) {
            $results[$key] = $videoService->debugModelResponse($key);
            sleep(1); // Pause entre les tests
        }
        
        return new JsonResponse([
            'models_tested' => $results,
            'recommendation' => 'Utilisez les modèles avec status_code 200 ou 503'
        ]);
    }

    #[Route('/test-video-generation', name: 'test_video_generation')]
    public function testVideoGeneration(HuggingFaceVideoService $videoService, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $prompt = $request->request->get('prompt', 'a cat walking');
            $model = $request->request->get('model', 'zeroscope-v2');
            
            $result = $videoService->generateVideo($prompt, $model);
            
            if ($result['success']) {
                $videoPath = $videoService->saveBase64Video(
                    $result['video_data'],
                    $result['mime_type']
                );
                
                return $this->render('test/video_result.html.twig', [
                    'success' => true,
                    'video_url' => '/' . $videoPath,
                    'prompt' => $prompt,
                    'model' => $model,
                    'details' => $result
                ]);
            }
            
            return $this->render('test/video_result.html.twig', [
                'success' => false,
                'error' => $result['error'],
                'details' => $result['details'] ?? null,
                'suggestion' => $result['suggestion'] ?? null,
                'prompt' => $prompt,
                'model' => $model
            ]);
        }
        
        return $this->render('test/video_form.html.twig', [
            'available_models' => $videoService->listAvailableModels()
        ]);
    }

    #[Route('/find-working-video-models', name: 'find_working_video_models')]
    public function findWorkingVideoModels(HuggingFaceVideoService $videoService): JsonResponse
    {
        $results = $videoService->findWorkingVideoModels();
        
        return new JsonResponse($results);
    }

    #[Route('/search-video-models', name: 'search_video_models')]
    public function searchVideoModels(HuggingFaceVideoService $videoService): JsonResponse
    {
        $results = $videoService->searchAvailableVideoModels();
        
        return new JsonResponse($results);
    }

    #[Route('/test-specific-video-model', name: 'test_specific_video_model')]
    public function testSpecificModel(HuggingFaceVideoService $videoService, Request $request): JsonResponse
    {
        $modelId = $request->query->get('model', 'ali-vilab/text-to-video-synthesis');
        $result = $videoService->testModelDirectly($modelId);
        
        return new JsonResponse([
            'model_tested' => $modelId,
            'result' => $result
        ]);
    }

}
