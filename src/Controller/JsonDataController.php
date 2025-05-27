<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;

class JsonDataController extends AbstractController
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/posts', name: 'app_posts')]
    public function index(): Response
    {
        try {
            $response = $this->httpClient->request('GET', 'https://jsonplaceholder.typicode.com/posts');
            
            $posts = $response->toArray();
            
            return $this->render('json_data/index.html.twig', [
                'posts' => $posts,
                'success' => true,
                'error' => null
            ]);
            
        } catch (\Exception $e) {
            return $this->render('json_data/index.html.twig', [
                'posts' => [],
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    #[Route('/posts/{id}', name: 'app_posts_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        try {
            // Récupérer un post spécifique
            $response = $this->httpClient->request('GET', "https://jsonplaceholder.typicode.com/posts/{$id}");
            
            if ($response->getStatusCode() === 404) {
                throw $this->createNotFoundException('Post non trouvé');
            }
            
            $post = $response->toArray();
            
            return $this->render('json_data/show.html.twig', [
                'post' => $post,
                'success' => true,
                'error' => null
            ]);
            
        } catch (\Exception $e) {
            return $this->render('json_data/show.html.twig', [
                'post' => null,
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    #[Route('/json-data/custom', name: 'app_json_data_custom', methods: ['GET', 'POST'])]
    public function custom(Request $request): Response
    {
        $data = null;
        $error = null;
        $url = '';

        if ($request->isMethod('POST')) {
            $url = $request->request->get('url');
            
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $error = 'URL invalide';
            } else {
                try {
                    $response = $this->httpClient->request('GET', $url, [
                        'timeout' => 30,
                        'headers' => [
                            'User-Agent' => 'Symfony HttpClient',
                            'Accept' => 'application/json',
                        ]
                    ]);
                    
                    if ($response->getStatusCode() !== 200) {
                        throw new \Exception('Erreur HTTP: ' . $response->getStatusCode());
                    }
                    
                    $data = $response->toArray();
                    
                } catch (\Exception $e) {
                    $error = 'Erreur lors de la récupération: ' . $e->getMessage();
                }
            }
        }

        return $this->render('json_data/custom.html.twig', [
            'data' => $data,
            'url' => $url,
            'error' => $error
        ]);
    }

    #[Route('/json-data/api/{endpoint}', name: 'app_json_data_api')]
    public function api(string $endpoint, Request $request): Response
    {
        // URLs prédéfinies pour différents endpoints
        $endpoints = [
            'posts' => 'https://jsonplaceholder.typicode.com/posts',
            'users' => 'https://jsonplaceholder.typicode.com/users',
            'comments' => 'https://jsonplaceholder.typicode.com/comments',
            'albums' => 'https://jsonplaceholder.typicode.com/albums',
            'photos' => 'https://jsonplaceholder.typicode.com/photos',
        ];

        if (!isset($endpoints[$endpoint])) {
            throw $this->createNotFoundException('Endpoint non trouvé');
        }

        $url = $endpoints[$endpoint];
        $limit = $request->query->get('limit', 10);

        try {
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();
            
            // Limiter le nombre de résultats si nécessaire
            if (is_array($data) && $limit > 0) {
                $data = array_slice($data, 0, (int)$limit);
            }

            return $this->render('json_data/api.html.twig', [
                'data' => $data,
                'endpoint' => $endpoint,
                'url' => $url,
                'limit' => $limit,
                'endpoints' => array_keys($endpoints)
            ]);

        } catch (\Exception $e) {
            return $this->render('json_data/api.html.twig', [
                'data' => null,
                'endpoint' => $endpoint,
                'url' => $url,
                'error' => $e->getMessage(),
                'endpoints' => array_keys($endpoints)
            ]);
        }
    }
}
