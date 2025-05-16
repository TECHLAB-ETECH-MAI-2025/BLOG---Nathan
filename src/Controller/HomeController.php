<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ArticleRepository;


class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ArticleRepository $articleRepository, Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 6; // 6 articles par page sur la page d'accueil
        
        $articles = $articleRepository->findPaginated($page, $limit);
        $total = $articleRepository->countAll();
        
        $maxPages = ceil($total / $limit);
        
        return $this->render('home/index.html.twig', [
            'articles' => $articles,
            'currentPage' => $page,
            'maxPages' => $maxPages,
            'limit' => $limit,
            'total' => $total
        ]);
    }

}
