<?php

namespace App\Controller;

use App\Service\VisaApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VisaResultController extends AbstractController
{
    #[Route('/visa-result/{amount}/{from}/{to}', name: 'visa_result')]
    public function show(VisaApiService $visaApiService, int $amount, string $from = 'EUR', string $to = 'USD'): Response
    {
        try {
            $result = $visaApiService->getCalculator($amount, $from, $to, 0);
            
            return $this->render('visa_result/show.html.twig', [
                'amount' => $amount,
                'from' => $from,
                'to' => $to,
                'result' => $result,
                'success' => true
            ]);
        } catch (\Exception $e) {
            return $this->render('visa_result/show.html.twig', [
                'amount' => $amount,
                'from' => $from,
                'to' => $to,
                'error' => $e->getMessage(),
                'success' => false
            ]);
        }
    }
}
