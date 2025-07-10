<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class HuggingFaceImageService
{
    private HttpClientInterface $httpClient;
    private string $apiToken;
    private array $availableModels;

    public function __construct(HttpClientInterface $httpClient, string $huggingFaceToken)
    {
        $this->httpClient = $httpClient;
        $this->apiToken = $huggingFaceToken;
        $this->availableModels = [
            'stable-diffusion-xl' => 'stabilityai/stable-diffusion-xl-base-1.0',
            'stable-diffusion-v1-5' => 'runwayml/stable-diffusion-v1-5',
            'flux-schnell' => 'black-forest-labs/FLUX.1-schnell',
            'dreamlike' => 'dreamlike-art/dreamlike-diffusion-1.0'
        ];
    }

    public function generateImage(string $prompt, string $model = 'stable-diffusion-xl'): array
    {
        if (!isset($this->availableModels[$model])) {
            return [
                'success' => false,
                'error' => "Modèle '$model' non disponible. Modèles disponibles: " . implode(', ', array_keys($this->availableModels))
            ];
        }

        $modelId = $this->availableModels[$model];
        
        try {
            $response = $this->httpClient->request('POST', 
                "https://api-inference.huggingface.co/models/$modelId",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'inputs' => $this->enhancePrompt($prompt),
                        'parameters' => [
                            'num_inference_steps' => 20,
                            'guidance_scale' => 7.5,
                            'width' => 1024,
                            'height' => 1024,
                        ],
                        'options' => [
                            'wait_for_model' => true,
                            'use_cache' => false
                        ]
                    ]
                ]
            );

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                // L'image est retournée en binaire
                $imageData = $response->getContent();
                $base64Image = base64_encode($imageData);
                
                return [
                    'success' => true,
                    'image_data' => $base64Image,
                    'mime_type' => 'image/jpeg',
                    'model_used' => $model,
                    'prompt_used' => $this->enhancePrompt($prompt)
                ];
            }

            // Gérer les erreurs
            $errorData = $response->toArray(false);
            
            if ($statusCode === 503 && isset($errorData['estimated_time'])) {
                return [
                    'success' => false,
                    'error' => 'Modèle en cours de chargement',
                    'estimated_time' => $errorData['estimated_time'],
                    'retry_after' => $errorData['estimated_time']
                ];
            }

            return [
                'success' => false,
                'error' => "Erreur API (Code: $statusCode): " . json_encode($errorData)
            ];

        } catch (TransportExceptionInterface $e) {
            return [
                'success' => false,
                'error' => 'Erreur de communication: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la génération: ' . $e->getMessage()
            ];
        }
    }

    private function enhancePrompt(string $prompt): string
    {
        // Améliorer le prompt pour de meilleurs résultats
        $qualityKeywords = [
            'high quality', 'professional', 'detailed', 'sharp focus',
            'masterpiece', 'best quality', 'ultra detailed'
        ];
        
        $negativePrompts = [
            'blurry', 'low quality', 'distorted', 'ugly', 'bad anatomy'
        ];

        return $prompt . ', ' . implode(', ', $qualityKeywords);
    }

    public function generateLandingPageImages(string $businessPrompt): array
    {
        $imagePrompts = [
            'hero' => "Professional business hero image for $businessPrompt, modern office environment, clean corporate style, high quality photography",
            'feature1' => "Modern technology illustration for $businessPrompt, minimalist design, professional icons, clean background",
            'feature2' => "Business team collaboration for $businessPrompt, professional workplace, modern office setting",
            'testimonial' => "Professional business person portrait for $businessPrompt, corporate headshot, clean background"
        ];

        $results = [];
        foreach ($imagePrompts as $type => $prompt) {
            $result = $this->generateImage($prompt);
            
            if ($result['success']) {
                // Sauvegarder l'image
                $imagePath = $this->saveBase64Image($result['image_data'], $result['mime_type'], $type);
                $result['image_path'] = $imagePath;
                $result['image_url'] = '/' . ltrim($imagePath, '/');
            }
            
            $results[$type] = $result;
            
            // Pause pour éviter le rate limiting
            if ($result['success']) {
                sleep(2); // Hugging Face a des limites de taux
            }
        }

        return $results;
    }

    public function saveBase64Image(string $base64Data, string $mimeType, string $prefix = 'hf'): string
    {
        $uploadDir = 'uploads/huggingface-images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg'
        };

        $filename = $prefix . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        $imageContent = base64_decode($base64Data);
        file_put_contents($filepath, $imageContent);

        return 'uploads/huggingface-images/' . $filename;
    }

    public function listAvailableModels(): array
    {
        return $this->availableModels;
    }

    public function testModelAvailability(string $model = 'stable-diffusion-xl'): array
    {
        return $this->generateImage('A simple red apple on white background, test image', $model);
    }
}
