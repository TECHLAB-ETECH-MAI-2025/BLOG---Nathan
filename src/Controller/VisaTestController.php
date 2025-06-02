<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VisaTestController extends AbstractController
{
    #[Route('/visa-test', name: 'visa_test')]
    public function index(): Response
    {
        return $this->render('visa_test/index.html.twig');
    }
}
