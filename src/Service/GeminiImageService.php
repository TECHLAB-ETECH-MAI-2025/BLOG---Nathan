<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GeminiImageService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $geminiApiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $geminiApiKey;
    }

    public function generateImage(string $prompt): array
    {
        try {
            $response = $this->httpClient->request('POST', 
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp-image-generation:generateContent', 
                [
                    'query' => [
                        'key' => $this->apiKey
                    ],
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    [
                                        'text' => "CREATE IMAGE: " . $prompt
                                    ]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.7,
                            'topK' => 40,
                            'topP' => 0.95,
                        ]
                    ]
                ]
            );

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);
            
            if ($statusCode !== 200) {
                return [
                    'success' => false,
                    'error' => "Erreur API (Code: $statusCode): " . json_encode($data)
                ];
            }
            
            // La réponse peut contenir l'image en base64 ou une URL
            if (isset($data['candidates'][0]['content']['parts'][0]['inlineData'])) {
                // Image en base64
                $imageData = $data['candidates'][0]['content']['parts'][0]['inlineData'];
                return [
                    'success' => true,
                    'image_type' => 'base64',
                    'image_data' => $imageData['data'],
                    'mime_type' => $imageData['mimeType']
                ];
            }
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                // Peut-être une URL ou une description
                return [
                    'success' => true,
                    'image_type' => 'text',
                    'content' => $data['candidates'][0]['content']['parts'][0]['text']
                ];
            }

            return [
                'success' => false,
                'error' => 'Format de réponse inattendu: ' . json_encode($data)
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

    private function enhancePromptForImageGeneration(string $prompt): string
    {
        return "GENERATE AN IMAGE: " . $prompt . 
            ". Create a visual image, not a text description. Output format: image file.";
    }


    public function saveBase64Image(string $base64Data, string $mimeType): string
    {
        // Créer le dossier s'il n'existe pas
        $uploadDir = 'public/uploads/generated-images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Déterminer l'extension
        $extension = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg'
        };

        // Générer un nom de fichier unique
        $filename = uniqid('gemini_img_') . '.' . $extension;
        $filepath = $uploadDir . $filename;

        // Décoder et sauvegarder
        $imageContent = base64_decode($base64Data);
        file_put_contents($filepath, $imageContent);

        return '/uploads/generated-images/' . $filename;
    }
}
