<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ReplicateVideoService
{
    private HttpClientInterface $httpClient;
    private string $apiToken;
    private array $availableModels;

    public function __construct(HttpClientInterface $httpClient, string $replicateToken)
    {
        $this->httpClient = $httpClient;
        $this->apiToken = $replicateToken;
        
        // Modèles vidéo disponibles sur Replicate
        $this->availableModels = [
            'zeroscope-v2' => [
                'id' => 'anotherjesse/zeroscope-v2-xl',
                'version' => '9f747673945c62801b13b84701c783929c0ee784e4748ec062204894dda1a351',
                'description' => 'Zeroscope V2 XL - Génération vidéo haute qualité'
            ],
            'stable-video' => [
                'id' => 'stability-ai/stable-video-diffusion',
                'version' => '3f0457e4619daac51203dedb1a4c069c4c7e0e5e4f49c44e5c5b8bd9de78a9a8',
                'description' => 'Stable Video Diffusion - Image vers vidéo'
            ],
            'animate-diff' => [
                'id' => 'lucataco/animate-diff',
                'version' => 'beecf59c4aee8d81bf04f0381033dfa10dc16e845b83c17a6b83a7a6b0b4c1d0',
                'description' => 'AnimateDiff - Animation à partir de texte'
            ],
            'runway-gen2' => [
                'id' => 'lucataco/runway-gen2',
                'version' => '2ecca8aa7d2e437b4b52c43b0b5e4e2b7e8b4e8b4e8b4e8b4e8b4e8b4e8b4e8b',
                'description' => 'Runway Gen-2 style - Génération vidéo cinématique'
            ]
        ];
    }

    public function generateVideo(string $prompt, string $model = 'zeroscope-v2', array $options = []): array
    {
        if (!isset($this->availableModels[$model])) {
            return [
                'success' => false,
                'error' => "Modèle '$model' non disponible. Modèles disponibles: " . implode(', ', array_keys($this->availableModels))
            ];
        }

        $modelInfo = $this->availableModels[$model];
        
        try {
            // Préparer les paramètres d'entrée selon le modèle
            $input = $this->prepareModelInput($prompt, $model, $options);
            
            $response = $this->httpClient->request('POST', 
                'https://api.replicate.com/v1/predictions',
                [
                    'headers' => [
                        'Authorization' => 'Token ' . $this->apiToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'version' => $modelInfo['version'],
                        'input' => $input
                    ]
                ]
            );

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 201) {
                $result = $response->toArray();
                
                return [
                    'success' => true,
                    'prediction_id' => $result['id'],
                    'status' => $result['status'],
                    'model_used' => $model,
                    'prompt_used' => $prompt,
                    'created_at' => $result['created_at'],
                    'urls' => $result['urls'] ?? [],
                    'message' => 'Génération vidéo démarrée. Utilisez checkVideoStatus() pour suivre le progrès.'
                ];
            }

            $errorData = $response->toArray(false);
            return [
                'success' => false,
                'error' => "Erreur API Replicate (Code: $statusCode)",
                'details' => $errorData
            ];

        } catch (TransportExceptionInterface $e) {
            return [
                'success' => false,
                'error' => 'Erreur de communication avec Replicate: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la génération: ' . $e->getMessage()
            ];
        }
    }

    public function checkVideoStatus(string $predictionId): array
    {
        try {
            $response = $this->httpClient->request('GET', 
                "https://api.replicate.com/v1/predictions/$predictionId",
                [
                    'headers' => [
                        'Authorization' => 'Token ' . $this->apiToken,
                    ]
                ]
            );

            if ($response->getStatusCode() === 200) {
                $result = $response->toArray();
                
                $status = [
                    'success' => true,
                    'prediction_id' => $predictionId,
                    'status' => $result['status'],
                    'created_at' => $result['created_at'],
                    'started_at' => $result['started_at'] ?? null,
                    'completed_at' => $result['completed_at'] ?? null,
                ];

                // Ajouter les résultats si terminé
                if ($result['status'] === 'succeeded' && isset($result['output'])) {
                    $status['output'] = $result['output'];
                    $status['video_url'] = is_array($result['output']) ? $result['output'][0] : $result['output'];
                    $status['completed'] = true;
                }

                // Ajouter les erreurs si échec
                if ($result['status'] === 'failed') {
                    $status['error'] = $result['error'] ?? 'Génération échouée';
                    $status['failed'] = true;
                }

                // Calculer le temps écoulé
                if (isset($result['started_at']) && isset($result['completed_at'])) {
                    $start = new \DateTime($result['started_at']);
                    $end = new \DateTime($result['completed_at']);
                    $status['duration_seconds'] = $end->getTimestamp() - $start->getTimestamp();
                }

                return $status;
            }

            return [
                'success' => false,
                'error' => 'Impossible de récupérer le statut de la prédiction'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la vérification du statut: ' . $e->getMessage()
            ];
        }
    }

    public function waitForVideoCompletion(string $predictionId, int $maxWaitSeconds = 300): array
    {
        $startTime = time();
        $checkInterval = 5; // Vérifier toutes les 5 secondes
        
        while ((time() - $startTime) < $maxWaitSeconds) {
            $status = $this->checkVideoStatus($predictionId);
            
            if (!$status['success']) {
                return $status;
            }

            // Terminé avec succès
            if ($status['status'] === 'succeeded') {
                return $status;
            }

            // Échec
            if ($status['status'] === 'failed') {
                return $status;
            }

            // Attendre avant la prochaine vérification
            sleep($checkInterval);
        }

        return [
            'success' => false,
            'error' => 'Timeout: La génération vidéo a pris plus de ' . $maxWaitSeconds . ' secondes',
            'prediction_id' => $predictionId,
            'timeout' => true
        ];
    }

    public function generateVideoAndWait(string $prompt, string $model = 'zeroscope-v2', array $options = []): array
    {
        // Démarrer la génération
        $generation = $this->generateVideo($prompt, $model, $options);
        
        if (!$generation['success']) {
            return $generation;
        }

        // Attendre la completion
        $result = $this->waitForVideoCompletion($generation['prediction_id']);
        
        if ($result['success'] && isset($result['video_url'])) {
            // Télécharger et sauvegarder la vidéo
            $localPath = $this->downloadAndSaveVideo($result['video_url']);
            
            if ($localPath) {
                $result['local_video_path'] = $localPath;
                $result['local_video_url'] = '/' . ltrim($localPath, '/');
            }
        }

        return $result;
    }

    private function prepareModelInput(string $prompt, string $model, array $options): array
    {
        $baseInput = [
            'prompt' => $prompt
        ];

        switch ($model) {
            case 'zeroscope-v2':
                return array_merge($baseInput, [
                    'num_frames' => $options['num_frames'] ?? 24,
                    'num_inference_steps' => $options['steps'] ?? 20,
                    'guidance_scale' => $options['guidance'] ?? 17.5,
                    'width' => $options['width'] ?? 1024,
                    'height' => $options['height'] ?? 576,
                    'fps' => $options['fps'] ?? 8
                ]);

            case 'stable-video':
                $input = [
                    'motion_bucket_id' => $options['motion_bucket_id'] ?? 127,
                    'fps' => $options['fps'] ?? 6,
                    'cond_aug' => $options['cond_aug'] ?? 0.02,
                    'decoding_t' => $options['decoding_t'] ?? 7
                ];
                
                // Stable Video nécessite une image de base
                if (isset($options['image_url'])) {
                    $input['image'] = $options['image_url'];
                } else {
                    $input['prompt'] = $prompt;
                }
                
                return $input;

            case 'animate-diff':
                return array_merge($baseInput, [
                    'num_frames' => $options['num_frames'] ?? 16,
                    'num_inference_steps' => $options['steps'] ?? 25,
                    'guidance_scale' => $options['guidance'] ?? 7.5
                ]);

            default:
                return $baseInput;
        }
    }

    private function downloadAndSaveVideo(string $videoUrl): ?string
    {
        try {
            $uploadDir = 'public/uploads/replicate-videos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = uniqid('replicate_video_') . '.mp4';
            $filepath = $uploadDir . $filename;

            $response = $this->httpClient->request('GET', $videoUrl);
            
            if ($response->getStatusCode() === 200) {
                file_put_contents($filepath, $response->getContent());
                return 'uploads/replicate-videos/' . $filename;
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
    }

    public function listAvailableModels(): array
    {
        return $this->availableModels;
    }

    public function getModelInfo(string $model): ?array
    {
        return $this->availableModels[$model] ?? null;
    }

    public function testConnection(): array
    {
        try {
            $response = $this->httpClient->request('GET', 
                'https://api.replicate.com/v1/account',
                [
                    'headers' => [
                        'Authorization' => 'Token ' . $this->apiToken,
                    ]
                ]
            );

            if ($response->getStatusCode() === 200) {
                $account = $response->toArray();
                return [
                    'success' => true,
                    'message' => 'Connexion Replicate réussie',
                    'account' => $account['username'] ?? 'Utilisateur',
                    'type' => $account['type'] ?? 'unknown'
                ];
            }

            return [
                'success' => false,
                'error' => 'Token Replicate invalide'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur de connexion Replicate: ' . $e->getMessage()
            ];
        }
    }
}
