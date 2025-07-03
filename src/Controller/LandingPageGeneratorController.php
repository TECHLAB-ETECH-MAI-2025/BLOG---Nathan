<?php

namespace App\Controller;

use App\Service\GeminiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LandingPageGeneratorController extends AbstractController
{
    private GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    #[Route('/landing-page-generator', name: 'landing_page_generator')]
    public function index(): Response
    {
        return $this->render('landing_page_generator/index.html.twig');
    }

    #[Route('/landing-page-generator/generate', name: 'landing_page_generate', methods: ['POST'])]
    public function generate(Request $request): Response
    {
        $prompt = $request->request->get('prompt');
        
        if (empty($prompt)) {
            $this->addFlash('error', 'Veuillez saisir un prompt pour générer votre landing page.');
            return $this->redirectToRoute('landing_page_generator');
        }

        $result = $this->geminiService->generateLandingPage($prompt);

        if ($result['success']) {
            return $this->render('landing_page_generator/result.html.twig', [
                'prompt' => $prompt,
                'generated_content' => $result['content']
            ]);
        } else {
            $this->addFlash('error', $result['error']);
            return $this->redirectToRoute('landing_page_generator');
        }
    }

    #[Route('/landing-page-generator/preview', name: 'landing_page_preview', methods: ['POST'])]
    public function preview(Request $request): Response
    {
        $htmlContent = $request->request->get('html_content');
        
        return new Response($htmlContent);
    }
}
