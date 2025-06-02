<?php

namespace App\Controller;

use App\Service\VisaApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class VisaApiController extends AbstractController
{
    #[Route('/api/exchange-rate-calculator', name: 'api_visa_exchange', methods: ['POST'])]
    public function calculate(Request $request, VisaApiService $visaApiService): JsonResponse
    {
        $amount = $request->request->get('amount');
        $fromCurr = $request->request->get('fromCurr');
        $toCurr = $request->request->get('toCurr');
        $fee = $request->request->get('fee', 0);

        if (!$amount || !$fromCurr || !$toCurr) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur du montant ou des devises'
            ], 200);
        }

        $content = $visaApiService->getCalculator((int)$amount, $fromCurr, $toCurr, (int)$fee);

        return new JsonResponse([
            'status' => 'success',
            'content' => $content
        ]);
    }

    #[Route('/api/exchange-rate-calculator/{amount}', name: 'api_visa_exchange_get', methods: ['GET'])]
    public function calculateGet(VisaApiService $visaApiService, int $amount): JsonResponse
    {
        $content = $visaApiService->getCalculator($amount);

        return new JsonResponse([
            'status' => 'success',
            'content' => $content
        ]);
    }
}
