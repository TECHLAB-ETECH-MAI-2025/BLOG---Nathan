<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class HuggingFaceVideoService
{
    private HttpClientInterface $httpClient;
    private string $apiToken;
    private array $availableModels;

    public function __construct(HttpClientInterface $httpClient, string $huggingFaceToken)
    {
        $this->httpClient = $httpClient;
        $this->apiToken = $huggingFaceToken;
        
        // Modèles RÉELLEMENT disponibles sur Hugging Face API
        $this->availableModels = [
            'ali-vilab-video' => 'ali-vilab/text-to-video-synthesis',
            'modelscope-video' => 'damo-vilab/text-to-video-ms-1.7b',
            'zeroscope-v2' => 'cerspense/zeroscope_v2_576w',
            'animatediff-v3' => 'guoyww/animatediff-motion-adapter-v1-5-3',
            'stable-video-diffusion' => 'stabilityai/stable-video-diffusion-img2vid-xt-1-1'
        ];
    }

    public function searchAvailableVideoModels(): array
    {
        // Rechercher les modèles vidéo disponibles
        try {
            $response = $this->httpClient->request('GET', 
                'https://huggingface.co/api/models',
                [
                    'query' => [
                        'pipeline_tag' => 'text-to-video',
                        'sort' => 'downloads',
                        'direction' => '-1',
                        'limit' => 10
                    ]
                ]
            );

            $models = $response->toArray();
            $availableModels = [];

            foreach ($models as $model) {
                if (isset($model['id']) && isset($model['pipeline_tag'])) {
                    $availableModels[] = [
                        'id' => $model['id'],
                        'downloads' => $model['downloads'] ?? 0,
                        'likes' => $model['likes'] ?? 0,
                        'pipeline_tag' => $model['pipeline_tag']
                    ];
                }
            }

            return [
                'success' => true,
                'models' => $availableModels
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function testModelDirectly(string $modelId): array
    {
        try {
            $response = $this->httpClient->request('POST', 
                "https://api-inference.huggingface.co/models/$modelId",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'inputs' => 'a simple test video of a cat'
                    ],
                    'timeout' => 30
                ]
            );

            $statusCode = $response->getStatusCode();
            $contentType = $response->getHeaders()['content-type'][0] ?? '';
            
            if ($statusCode === 404) {
                return [
                    'success' => false,
                    'model' => $modelId,
                    'error' => 'Modèle non trouvé (404)',
                    'available' => false
                ];
            }

            if ($statusCode === 503) {
                $errorData = $response->toArray(false);
                return [
                    'success' => false,
                    'model' => $modelId,
                    'error' => 'Modèle en cours de chargement',
                    'estimated_time' => $errorData['estimated_time'] ?? 'Inconnu',
                    'available' => true, // Le modèle existe mais n'est pas chargé
                    'status' => 'loading'
                ];
            }

            if ($statusCode === 200) {
                return [
                    'success' => true,
                    'model' => $modelId,
                    'status_code' => $statusCode,
                    'content_type' => $contentType,
                    'available' => true,
                    'status' => 'ready'
                ];
            }

            // Autres codes d'erreur
            $errorData = $response->toArray(false);
            return [
                'success' => false,
                'model' => $modelId,
                'status_code' => $statusCode,
                'error' => $errorData,
                'available' => false
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'model' => $modelId,
                'error' => $e->getMessage(),
                'available' => false
            ];
        }
    }

    public function findWorkingVideoModels(): array
    {
        // Liste de modèles à tester (plus récents et populaires)
        $modelsToTest = [
            'ali-vilab/text-to-video-synthesis',
            'damo-vilab/text-to-video-ms-1.7b',
            'VideoCrafter/VideoCrafter2',
            'runwayml/stable-video-diffusion-img2vid-xt',
            'stabilityai/stable-video-diffusion-img2vid-xt-1-1',
            'cerspense/zeroscope_v2_576w',
            'cerspense/zeroscope_v2_XL',
            'guoyww/animatediff-motion-adapter-v1-5-2',
            'ByteDance/AnimateDiff-Lightning',
            'hotshotco/Hotshot-XL'
        ];

        $results = [];
        foreach ($modelsToTest as $modelId) {
            $result = $this->testModelDirectly($modelId);
            $results[$modelId] = $result;
            
            // Pause pour éviter le rate limiting
            sleep(1);
        }

        // Filtrer les modèles disponibles
        $workingModels = array_filter($results, function($result) {
            return $result['available'] ?? false;
        });

        return [
            'all_tests' => $results,
            'working_models' => $workingModels,
            'working_count' => count($workingModels),
            'total_tested' => count($results)
        ];
    }

    public function generateVideo(string $prompt, string $modelId): array
    {
        // Vérifier d'abord si le modèle existe
        $modelTest = $this->testModelDirectly($modelId);
        
        if (!$modelTest['available']) {
            return [
                'success' => false,
                'error' => "Modèle '$modelId' non disponible",
                'model_test' => $modelTest
            ];
        }

        try {
            $response = $this->httpClient->request('POST', 
                "https://api-inference.huggingface.co/models/$modelId",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'inputs' => $prompt,
                        'options' => [
                            'wait_for_model' => true
                        ]
                    ],
                    'timeout' => 300
                ]
            );

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                $contentType = $response->getHeaders()['content-type'][0] ?? '';
                
                if (str_contains($contentType, 'video') || str_contains($contentType, 'octet-stream')) {
                    $videoData = $response->getContent();
                    $base64Video = base64_encode($videoData);
                    
                    return [
                        'success' => true,
                        'video_data' => $base64Video,
                        'mime_type' => 'video/mp4',
                        'model_used' => $modelId,
                        'prompt_used' => $prompt,
                        'content_type' => $contentType,
                        'size_bytes' => strlen($videoData)
                    ];
                }
            }

            $errorData = $response->toArray(false);
            return [
                'success' => false,
                'error' => "Erreur API (Code: $statusCode)",
                'details' => $errorData
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la génération: ' . $e->getMessage()
            ];
        }
    }

    public function saveBase64Video(string $base64Data, string $mimeType): string
    {
        $uploadDir = 'uploads/generated-videos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid('hf_video_') . '.mp4';
        $filepath = $uploadDir . $filename;

        $videoContent = base64_decode($base64Data);
        file_put_contents($filepath, $videoContent);

        return 'uploads/generated-videos/' . $filename;
    }
}
