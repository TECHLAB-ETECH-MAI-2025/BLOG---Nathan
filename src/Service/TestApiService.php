<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class TestApiService
{
    private HttpClientInterface $httpClient;
    private ?LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger = null)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    public function testAccess(): array
    {
        $cookies = $_ENV['CHATGPT_COOKIES'] ?? '';
        
        try {
            // Test 1: Page principale
            $response = $this->httpClient->request('GET', 'https://chatgpt.com/', [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Cookie' => $cookies,
                ],
                'timeout' => 30
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent();
            
            return [
                'status' => $statusCode,
                'success' => $statusCode === 200,
                'content_length' => strlen($content),
                'is_blocked' => strpos($content, 'blocked') !== false || strpos($content, 'Access denied') !== false,
                'has_react' => strpos($content, '__NEXT_DATA__') !== false,
                'content_preview' => substr($content, 0, 500)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'type' => get_class($e)
            ];
        }
    }

    public function getLandingPage(string $prompt): array
    {
        // D'abord tester l'accès
        $accessTest = $this->testAccess();
        if (!$accessTest['success']) {
            throw new \Exception('Impossible d\'accéder à ChatGPT: ' . ($accessTest['error'] ?? 'Accès bloqué'));
        }

        $cookies = $_ENV['CHATGPT_COOKIES'] ?? '';
        
        // Attendre un peu
        sleep(1);

        // Essayer une approche différente - utiliser l'API publique si elle existe
        try {
            return $this->tryAlternativeApproach($prompt, $cookies);
        } catch (\Exception $e) {
            // Si ça échoue, essayer l'API backend
            return $this->tryBackendApi($prompt, $cookies);
        }
    }

    private function tryAlternativeApproach(string $prompt, string $cookies): array
    {
        // Essayer d'abord avec une requête GET simple pour voir les endpoints disponibles
        $response = $this->httpClient->request('GET', 'https://chatgpt.com/backend-api/models', [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept' => 'application/json',
                'Cookie' => $cookies,
            ],
            'timeout' => 30
        ]);

        $statusCode = $response->getStatusCode();
        $content = $response->getContent();

        return [
            'method' => 'models_endpoint',
            'status' => $statusCode,
            'content' => json_decode($content, true) ?: $content
        ];
    }

    private function tryBackendApi(string $prompt, string $cookies): array
    {
        $payload = [
            'action' => 'next',
            'messages' => [
                [
                    'id' => $this->generateUuid(),
                    'author' => ['role' => 'user'],
                    'create_time' => time(),
                    'content' => [
                        'content_type' => 'text',
                        'parts' => [$prompt]
                    ]
                ]
            ],
            'parent_message_id' => $this->generateUuid(),
            'model' => 'text-davinci-002-render-sha'
        ];

        $response = $this->httpClient->request('POST', 'https://chatgpt.com/backend-api/conversation', [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept' => 'text/event-stream',
                'Content-Type' => 'application/json',
                'Origin' => 'https://chatgpt.com',
                'Referer' => 'https://chatgpt.com/',
                'Cookie' => $cookies,
            ],
            'json' => $payload,
            'timeout' => 60
        ]);

        return [
            'method' => 'backend_api',
            'status' => $response->getStatusCode(),
            'content' => $response->getContent()
        ];
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
