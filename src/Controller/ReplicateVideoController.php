<?php

namespace App\Controller;

use App\Service\ReplicateVideoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReplicateVideoController extends AbstractController
{
    #[Route('/test-replicate-connection', name: 'test_replicate_connection')]
    public function testConnection(ReplicateVideoService $replicateService): JsonResponse
    {
        $result = $replicateService->testConnection();
        return new JsonResponse($result);
    }

    #[Route('/replicate-video-generation', name: 'replicate_video_generation')]
    public function videoGeneration(ReplicateVideoService $replicateService, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $prompt = $request->request->get('prompt', 'A cat walking in a beautiful garden');
            $model = $request->request->get('model', 'zeroscope-v2');
            $waitForCompletion = $request->request->get('wait_completion', false);
            
            // Options avancées
            $options = [
                'num_frames' => (int)$request->request->get('num_frames', 24),
                'steps' => (int)$request->request->get('steps', 20),
                'guidance' => (float)$request->request->get('guidance', 17.5),
                'width' => (int)$request->request->get('width', 1024),
                'height' => (int)$request->request->get('height', 576),
                'fps' => (int)$request->request->get('fps', 8)
            ];
            
            if ($waitForCompletion) {
                // Génération synchrone (attendre la fin)
                $result = $replicateService->generateVideoAndWait($prompt, $model, $options);
                
                return $this->render('replicate/video_result.html.twig', [
                    'result' => $result,
                    'prompt' => $prompt,
                    'model' => $model,
                    'options' => $options,
                    'synchronous' => true
                ]);
            } else {
                // Génération asynchrone (démarrer seulement)
                $result = $replicateService->generateVideo($prompt, $model, $options);
                
                return $this->render('replicate/video_result.html.twig', [
                    'result' => $result,
                    'prompt' => $prompt,
                    'model' => $model,
                    'options' => $options,
                    'synchronous' => false
                ]);
            }
        }
        
        return $this->render('replicate/video_form.html.twig', [
            'available_models' => $replicateService->listAvailableModels()
        ]);
    }

    #[Route('/check-video-status/{predictionId}', name: 'check_video_status')]
    public function checkVideoStatus(string $predictionId, ReplicateVideoService $replicateService): JsonResponse
    {
        $result = $replicateService->checkVideoStatus($predictionId);
        
        // Si la vidéo est terminée, télécharger et sauvegarder
        if ($result['success'] && $result['status'] === 'succeeded' && isset($result['video_url'])) {
            $localPath = $replicateService->downloadAndSaveVideo($result['video_url']);
            if ($localPath) {
                $result['local_video_path'] = $localPath;
                $result['local_video_url'] = '/' . ltrim($localPath, '/');
            }
        }
        
        return new JsonResponse($result);
    }

    #[Route('/replicate-video-status/{predictionId}', name: 'replicate_video_status_page')]
    public function videoStatusPage(string $predictionId, ReplicateVideoService $replicateService): Response
    {
        $result = $replicateService->checkVideoStatus($predictionId);
        
        return $this->render('replicate/video_status.html.twig', [
            'prediction_id' => $predictionId,
            'result' => $result
        ]);
    }

    #[Route('/list-replicate-models', name: 'list_replicate_models')]
    public function listModels(ReplicateVideoService $replicateService): JsonResponse
    {
        return new JsonResponse([
            'available_models' => $replicateService->listAvailableModels()
        ]);
    }
}
