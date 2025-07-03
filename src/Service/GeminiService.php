<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GeminiService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $geminiApiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $geminiApiKey;
    }

    public function generateLandingPage(string $prompt): array
    {
        try {
            $response = $this->httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent', [
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
                                    'text' => $this->buildLandingPagePrompt($prompt)
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 8192,
                    ]
                ]
            ]);

            $data = $response->toArray();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return [
                    'success' => true,
                    'content' => $data['candidates'][0]['content']['parts'][0]['text']
                ];
            }

            return [
                'success' => false,
                'error' => 'Aucun contenu généré'
            ];

        } catch (TransportExceptionInterface $e) {
            return [
                'success' => false,
                'error' => 'Erreur de communication avec l\'API Gemini: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la génération: ' . $e->getMessage()
            ];
        }
    }

    private function buildLandingPagePrompt(string $userPrompt): string
    {
        return "Tu es un expert en création de landing pages. Génère une landing page HTML complète et moderne basée sur cette demande : \"$userPrompt\"

La landing page doit inclure :
1. Une structure HTML5 sémantique complète
2. Du CSS moderne et responsive (inclus dans des balises <style>)
3. Un header avec navigation
4. Une section hero accrocheuse
5. Des sections pour les fonctionnalités/avantages
6. Une section témoignages
7. Un call-to-action clair
8. Un footer
9. Du JavaScript pour les interactions (inclus dans des balises <script>)

Utilise des couleurs modernes, des animations CSS subtiles, et assure-toi que le design soit responsive.
Retourne uniquement le code HTML complet, prêt à être utilisé.";
    }
}
