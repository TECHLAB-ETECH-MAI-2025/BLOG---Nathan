<?php

namespace App\Controller;

use App\Entity\ConversionDevise;
use App\Form\ConversionDeviseType;
use App\Repository\ConversionDeviseRepository;
use App\Service\ConvertisseurDeviseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/convertisseur')]
class ConvertisseurDeviseController extends AbstractController
{
    private ConvertisseurDeviseService $convertisseurService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ConvertisseurDeviseService $convertisseurService,
        EntityManagerInterface $entityManager
    ) {
        $this->convertisseurService = $convertisseurService;
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_convertisseur_devise')]
    public function index(Request $request): Response
    {
        $conversion = new ConversionDevise();
        $form = $this->createForm(ConversionDeviseType::class, $conversion);
        
        $form->handleRequest($request);
        $resultat = null;
        $erreur = null;

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if ($conversion->getDeviseSource() === $conversion->getDeviseCible()) {
                    $this->addFlash('warning', 'Les devises source et cible doivent être différentes.');
                } else {
                    $resultatConversion = $this->convertisseurService->convertirAvecVisa(
                        (float) $conversion->getMontant(),
                        $conversion->getDeviseSource(),
                        $conversion->getDeviseCible(),
                        $conversion->getDateTransaction(),
                        $conversion->getFraisBancaires() ? (float) $conversion->getFraisBancaires() : 0
                    );

                    // Traiter le résultat même en cas de fallback
                    $conversion->setTauxChange((string) $resultatConversion['taux_change']);
                    $conversion->setMontantConverti((string) $resultatConversion['montant_converti']);
                    $conversion->setMontantFinal((string) $resultatConversion['montant_converti']);

                    if ($this->getUser()) {
                        $conversion->setUtilisateur($this->getUser());
                    }

                    $this->entityManager->persist($conversion);
                    $this->entityManager->flush();

                    $resultat = [
                        'conversion' => $conversion,
                        'donnees_visa' => $resultatConversion['donnees_brutes'],
                        'url_api' => $resultatConversion['url_api'],
                        'montant_formate_source' => $this->convertisseurService->formaterMontant(
                            (float) $conversion->getMontant(),
                            $conversion->getDeviseSource()
                        ),
                        'montant_formate_converti' => $this->convertisseurService->formaterMontant(
                            (float) $conversion->getMontantConverti(),
                            $conversion->getDeviseCible()
                        ),
                        'date_conversion_visa' => $resultatConversion['date_conversion'],
                        'taux_formate' => number_format($resultatConversion['taux_change'], 6, ',', ' '),
                        'is_fallback' => $resultatConversion['fallback'] ?? false,
                        'api_error' => $resultatConversion['error'] ?? null
                    ];

                    if (isset($resultatConversion['fallback']) && $resultatConversion['fallback']) {
                        $this->addFlash('warning', 'API Visa indisponible. Taux fictifs utilisés pour la démonstration.');
                    } else {
                        $this->addFlash('success', 'Conversion effectuée avec succès via l\'API Visa !');
                    }
                }

            } catch (\Exception $e) {
                $erreur = $e->getMessage();
                $this->addFlash('error', 'Erreur lors de la conversion : ' . $erreur);
            }
        }

        return $this->render('convertisseur_devise/index.html.twig', [
            'form' => $form->createView(),
            'resultat' => $resultat,
            'erreur' => $erreur
        ]);
    }

    #[Route('/historique', name: 'app_convertisseur_historique')]
    public function historique(ConversionDeviseRepository $repository): Response
    {
        $conversions = [];
        
        if ($this->getUser()) {
            $conversions = $repository->findBy(
                ['utilisateur' => $this->getUser()],
                ['createdAt' => 'DESC'],
                20
            );
        } else {
            $conversions = $repository->findBy(
                [],
                ['createdAt' => 'DESC'],
                10
            );
        }

        return $this->render('convertisseur_devise/historique.html.twig', [
            'conversions' => $conversions,
            'convertisseur_service' => $this->convertisseurService
        ]);
    }

    #[Route('/api/visa/{source}/{cible}/{montant}', name: 'app_convertisseur_api_visa', methods: ['GET'])]
    public function getConversionVisa(string $source, string $cible, float $montant, Request $request): Response
    {
        try {
            $date = $request->query->get('date') ? new \DateTime($request->query->get('date')) : new \DateTime();
            $frais = (float) $request->query->get('frais', 0);

            $resultat = $this->convertisseurService->convertirAvecVisa(
                $montant,
                strtoupper($source),
                strtoupper($cible),
                $date,
                $frais
            );
            
            return $this->json($resultat);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/supprimer/{id}', name: 'app_convertisseur_supprimer', methods: ['POST'])]
    public function supprimer(ConversionDevise $conversion): Response
    {
        if ($conversion->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($conversion);
        $this->entityManager->flush();

        $this->addFlash('success', 'Conversion supprimée avec succès.');

        return $this->redirectToRoute('app_convertisseur_historique');
    }

    #[Route('/test-api', name: 'app_convertisseur_test_api')]
    public function testApi(): Response
    {
        $resultat = $this->convertisseurService->testerApiVisa();
        
        return $this->json($resultat);
    }

}

