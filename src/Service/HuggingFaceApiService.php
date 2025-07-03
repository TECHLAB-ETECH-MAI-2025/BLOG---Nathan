<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class HuggingFaceApiService
{
    private HttpClientInterface $httpClient;
    private array $models = [
        'gpt2',
        'microsoft/DialoGPT-medium',
        'facebook/blenderbot-400M-distill',
        'EleutherAI/gpt-neo-125M'
    ];

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function generateLandingPage(string $prompt): array
    {
        $token = $_ENV['HUGGINGFACE_TOKEN'] ?? '';
        
        if (empty($token)) {
            throw new \Exception('Token HuggingFace manquant. Ajoutez HUGGINGFACE_TOKEN dans votre .env');
        }

        // Essayer plusieurs modèles jusqu'à ce qu'un fonctionne
        foreach ($this->models as $model) {
            try {
                $result = $this->tryModel($model, $prompt, $token);
                if ($result['success']) {
                    return $result;
                }
            } catch (\Exception $e) {
                // Continuer avec le modèle suivant
                continue;
            }
        }
        return $this->generateIntelligentTemplate($prompt);
    }

    private function tryModel(string $model, string $prompt, string $token): array
    {
        $enhancedPrompt = $this->createPromptForModel($model, $prompt);

        $response = $this->httpClient->request('POST', "https://api-inference.huggingface.co/models/{$model}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'inputs' => $enhancedPrompt,
                'parameters' => $this->getParametersForModel($model),
                'options' => [
                    'wait_for_model' => false,
                    'use_cache' => true
                ]
            ],
            'timeout' => 15 // Timeout plus court
        ]);

        $result = $response->toArray();
        
        // Vérifier si le modèle est en cours de chargement
        if (isset($result['error'])) {
            if (strpos($result['error'], 'loading') !== false) {
                throw new \Exception("Modèle {$model} en cours de chargement");
            }
            throw new \Exception("Erreur modèle {$model}: " . $result['error']);
        }

        // Traiter la réponse selon le modèle
        $html = $this->processModelResponse($result, $prompt, $model);
        
        return [
            'success' => true,
            'status' => 'success',
            'html' => $html,
            'method' => "huggingface_{$model}",
            'raw_response' => $result
        ];
    }

    private function createPromptForModel(string $model, string $prompt): string
    {
        switch ($model) {
            case 'gpt2':
                return "Create a professional HTML landing page for {$prompt}. Include modern CSS styling:\n\n<!DOCTYPE html>\n<html>\n<head>\n<title>";
                
            case 'microsoft/DialoGPT-medium':
            case 'microsoft/DialoGPT-large':
                return "User: I need a complete HTML landing page for {$prompt} with modern design and CSS.\nBot: Here's a professional landing page:\n\n<!DOCTYPE html>";
                
            case 'facebook/blenderbot-400M-distill':
                return "Create HTML landing page for {$prompt} with CSS styling";
                
            case 'EleutherAI/gpt-neo-125M':
                return "HTML landing page code for {$prompt}:\n<!DOCTYPE html>\n<html lang=\"en\">\n<head>";
                
            default:
                return "Generate HTML landing page for: {$prompt}";
        }
    }

    private function getParametersForModel(string $model): array
    {
        switch ($model) {
            case 'gpt2':
            case 'EleutherAI/gpt-neo-125M':
                return [
                    'max_length' => 800,
                    'temperature' => 0.7,
                    'do_sample' => true,
                    'top_p' => 0.9,
                    'return_full_text' => false
                ];
                
            case 'microsoft/DialoGPT-medium':
            case 'microsoft/DialoGPT-large':
                return [
                    'max_length' => 1000,
                    'temperature' => 0.6,
                    'do_sample' => true,
                    'pad_token_id' => 50256
                ];
                
            case 'facebook/blenderbot-400M-distill':
                return [
                    'max_length' => 500,
                    'temperature' => 0.8
                ];
                
            default:
                return [
                    'max_length' => 800,
                    'temperature' => 0.7
                ];
        }
    }

    private function processModelResponse(array $result, string $prompt, string $model): string
    {
        $generatedText = '';
        
        // Extraire le texte selon le format de réponse du modèle
        if (isset($result[0]['generated_text'])) {
            $generatedText = $result[0]['generated_text'];
        } elseif (isset($result['generated_text'])) {
            $generatedText = $result['generated_text'];
        } elseif (isset($result[0]['text'])) {
            $generatedText = $result[0]['text'];
        }

        // Si on a du HTML valide, l'utiliser
        if ($this->isValidHtml($generatedText)) {
            return $this->completeHtml($generatedText);
        }

    }

    private function isValidHtml(string $text): bool
    {
        return (strpos($text, '<!DOCTYPE') !== false || 
                strpos($text, '<html') !== false || 
                strpos($text, '<head') !== false);
    }

    private function completeHtml(string $html): string
    {
        // Compléter le HTML s'il est incomplet
        if (strpos($html, '</html>') === false) {
            if (strpos($html, '</body>') === false) {
                $html .= "\n</body>";
            }
            $html .= "\n</html>";
        }
        
        return $html;
    }

    private function wrapContentInHtml(string $content, string $prompt, string $model): string
    {
        $title = ucfirst($prompt);
        
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .ai-content {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin: 2rem 0;
        }
        .cta {
            background: #ff6b6b;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 50px;
            display: inline-block;
            margin: 20px 0;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        .cta:hover {
            transform: translateY(-2px);
        }
        .model-info {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            font-size: 0.9rem;
            color: #1565c0;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }
        .feature {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .feature h3 {
            color: #667eea;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="hero">
        <h1>{$title}</h1>
        <p>Généré par Intelligence Artificielle</p>
        <a href="#content" class="cta">Découvrir</a>
    </div>
    
    <div class="container">
        <div class="model-info">
            <strong>✨ Généré par:</strong> {$model} via HuggingFace API
        </div>
        
        <div class="ai-content" id="content">
            <h2>Contenu généré par IA</h2>
            <div style="white-space: pre-wrap; line-height: 1.8;">{$content}</div>
        </div>
        
        <div class="features">
            <div class="feature">
                <h3>🚀 Rapide</h3>
                <p>Génération instantanée de contenu</p>
            </div>
            <div class="feature">
                <h3>🎨 Moderne</h3>
                <p>Design responsive et attractif</p>
            </div>
            <div class="feature">
                <h3>🤖 IA</h3>
                <p>Powered by HuggingFace</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function generateIntelligentTemplate(string $prompt): array
    {
        $title = ucfirst($prompt);
        $businessType = $this->detectBusinessType($prompt);
        $colors = $this->getColorScheme($businessType);
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - Landing Page Professionnelle</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        .hero {
            background: {$colors['gradient']};
            color: white;
            padding: 5rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.1);
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
            animation: fadeInUp 1s ease;
        }
        
        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.95;
            animation: fadeInUp 1s ease 0.3s both;
        }
        
        .cta-primary {
            background: {$colors['cta']};
            color: white;
            padding: 18px 40px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            display: inline-block;
            transition: all 0.3s ease;
            animation: fadeInUp 1s ease 0.6s both;
        }
        
        .cta-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .section {
            padding: 5rem 0;
        }
        
        .features {
            background: #f8f9fa;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #2c3e50;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2.5rem;
        }
        
        .feature-card {
            background: white;
            padding: 3rem 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-top: 4px solid {$colors['accent']};
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        .feature-icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            display: block;
        }
        
        .feature-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .feature-description {
            color: #7f8c8d;
            line-height: 1.8;
        }
        
        .stats {
            background: {$colors['secondary_gradient']};
            color: white;
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .stat-item {
            padding: 1rem;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .cta-section {
            background: white;
            text-align: center;
        }
        
        .cta-content {
            max-width: 600px;
            margin: 0 auto;
        }
        
        footer {
            background: #2c3e50;
            color: white;
            padding: 3rem 0;
            text-align: center;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-section h3 {
            margin-bottom: 1rem;
            color: {$colors['accent']};
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero {
                padding: 3rem 1rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <section class="hero">
        <div class="hero-content">
            <h1>{$this->generateTitle($prompt, $businessType)}</h1>
            <p>{$this->generateSubtitle($prompt, $businessType)}</p>
            <a href="#features" class="cta-primary">{$this->generateCTA($businessType)}</a>
        </div>
    </section>

    <section class="section features" id="features">
        <div class="container">
            <h2 class="section-title">Pourquoi nous choisir ?</h2>
            <div class="features-grid">
                {$this->generateFeatures($businessType)}
            </div>
        </div>
    </section>

    <section class="section stats">
        <div class="container">
            <h2 class="section-title">Nos résultats</h2>
            <div class="stats-grid">
                {$this->generateStats($businessType)}
            </div>
        </div>
    </section>

    <section class="section cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Prêt à commencer ?</h2>
                <p>Rejoignez des milliers de clients satisfaits dès aujourd'hui.</p>
                <a href="#contact" class="cta-primary">Contactez-nous</a>
            </div>
        </div>
    </section>

    <footer id="contact">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3>Contact</h3>
                    <p>Email: contact@{$this->generateDomain($prompt)}</p>
                    <p>Téléphone: +33 1 23 45 67 89</p>
                </div>
                <div class="footer-section">
                    <h3>Services</h3>
                    <p>{$this->generateServices($businessType)}</p>
                </div>
                <div class="footer-section">
                    <h3>Suivez-nous</h3>
                    <p>Facebook | Twitter | LinkedIn</p>
                </div>
            </div>
            <p>&copy; 2024 {$title}. Tous droits réservés.</p>
        </div>
    </footer>
</body>
</html>
HTML;

        return [
            'success' => true,
            'status' => 'success',
            'html' => $html,
            'method' => 'intelligent_template',
            'business_type' => $businessType
        ];
    }

    private function tryAlternativeModel(string $prompt, string $token): array
    {
        // Essayer avec un modèle de génération de code
        try {
            $response = $this->httpClient->request('POST', 'https://api-inference.huggingface.co/models/Salesforce/codegen-350M-mono', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => "<!-- HTML landing page for: {$prompt} -->\n<!DOCTYPE html>\n<html>\n<head>\n<title>",
                    'parameters' => [
                        'max_length' => 1500,
                        'temperature' => 0.3,
                        'do_sample' => true
                    ]
                ],
                'timeout' => 30
            ]);

            $result = $response->toArray();
            
            return [
                'status' => 'success',
                'html' => $this->extractAndCompleteHtml($result),
                'raw_response' => $result,
                'method' => 'huggingface_codegen'
            ];

        } catch (\Exception $e) {
            // Essayer avec GPT-2
            return $this->tryGpt2Model($prompt, $token);
        }
    }

    private function tryGpt2Model(string $prompt, string $token): array
    {
        try {
            $response = $this->httpClient->request('POST', 'https://api-inference.huggingface.co/models/gpt2', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => "Create HTML landing page for {$prompt}:\n<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n<meta charset=\"UTF-8\">\n<title>",
                    'parameters' => [
                        'max_length' => 1000,
                        'temperature' => 0.7,
                        'return_full_text' => false
                    ]
                ],
                'timeout' => 30
            ]);

            $result = $response->toArray();
            
            return [
                'status' => 'success',
                'html' => $this->buildCompleteHtml($result, $prompt),
                'raw_response' => $result,
                'method' => 'huggingface_gpt2'
            ];

        } catch (\Exception $e) {
            throw new \Exception('Tous les modèles HuggingFace ont échoué: ' . $e->getMessage());
        }
    }

    private function extractHtml($result): string
    {
        if (isset($result[0]['generated_text'])) {
            $text = $result[0]['generated_text'];
            
            // Chercher du HTML dans la réponse
            if (strpos($text, '<!DOCTYPE') !== false || strpos($text, '<html') !== false) {
                return $text;
            }
        }

        // Si pas de HTML valide, créer un template avec le contenu généré
        return $this->wrapInHtmlTemplate($result);
    }

    private function extractAndCompleteHtml($result): string
    {
        $generatedCode = '';
        
        if (isset($result[0]['generated_text'])) {
            $generatedCode = $result[0]['generated_text'];
        }

        // Compléter le HTML si incomplet
        if (!strpos($generatedCode, '</html>')) {
            $generatedCode .= "\n</body>\n</html>";
        }

        return $generatedCode;
    }

    private function buildCompleteHtml($result, string $prompt): string
    {
        $generatedContent = '';
        
        if (isset($result[0]['generated_text'])) {
            $generatedContent = $result[0]['generated_text'];
        }

        // Construire un HTML complet avec le contenu généré
        return $this->createHtmlFromContent($generatedContent, $prompt);
    }

    private function wrapInHtmlTemplate($result): string
    {
        $content = isset($result[0]['generated_text']) ? $result[0]['generated_text'] : 'Contenu généré par IA';
        
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page Générée</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; }
        .ai-content { background: #f4f4f4; padding: 20px; border-radius: 8px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Landing Page Générée par IA</h1>
        <div class="ai-content">
            <pre>{$content}</pre>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function createHtmlFromContent(string $content, string $prompt): string
    {
        $title = ucfirst($prompt);
        
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: #333;
        }
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .ai-generated {
            background: white;
            padding: 3rem 2rem;
            margin: 2rem 0;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .cta {
            background: #ff6b6b;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="hero">
        <h1>{$title}</h1>
        <p>Généré par Intelligence Artificielle</p>
    </div>
    
    <div class="container">
        <div class="ai-generated">
            <h2>Contenu généré :</h2>
            <div>{$content}</div>
            <a href="#contact" class="cta">En savoir plus</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function generateFallbackTemplate(string $prompt, string $error): array
    {
        $title = ucfirst($prompt);
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - Landing Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: #333;
        }
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        .error-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="hero">
        <h1>{$title}</h1>
        <p>Landing Page Template</p>
    </div>
    
    <div class="container">
        <h2>À propos de {$title}</h2>
        <p>Cette landing page a été générée automatiquement pour : <strong>{$prompt}</strong></p>
        
        <div class="error-info">
            <strong>Info:</strong> L'API HuggingFace n'était pas disponible. Template de fallback utilisé.
            <br><small>Erreur: {$error}</small>
        </div>
        
        <h3>Fonctionnalités</h3>
        <ul>
            <li>Solution moderne et efficace</li>
            <li>Interface utilisateur intuitive</li>
            <li>Support client réactif</li>
        </ul>
    </div>
</body>
</html>
HTML;

        return [
            'status' => 'fallback',
            'html' => $html,
            'error' => $error,
            'method' => 'fallback_template'
        ];
    }
}
