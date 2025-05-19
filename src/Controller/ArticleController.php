<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Comment;
use App\Form\CommentType;


#[Route('/article')]
class ArticleController extends AbstractController
{
    #[Route('/', name: 'app_article_index', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository, Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 2;
        
        $articles = $articleRepository->findPaginated($page, $limit);
        $total = $articleRepository->countAll();
        
        $maxPages = ceil($total / $limit);
        
        return $this->render('article/index.html.twig', [
            'articles' => $articles,
            'currentPage' => $page,
            'maxPages' => $maxPages,
            'limit' => $limit,
            'total' => $total
        ]);
    }


    #[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_article_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();
        $comment->setArticle($article);
        $comment->setCreatedAt(new \DateTimeImmutable());
        
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre commentaire a été ajouté avec succès !');
            
            return $this->redirectToRoute('app_article_show', ['id' => $article->getId()], Response::HTTP_SEE_OTHER);
        }
        
        
        return $this->render('article/show.html.twig', [
            'article' => $article,
            'commentForm' => $form,
        ]);
    }


    #[Route('/{id}/edit', name: 'app_article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) {
            foreach ($article->getComments() as $comment) {
                $entityManager->remove($comment);
            }
            $entityManager->remove($article);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'article a été supprimé avec succès.');
            
            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }
        
        $this->addFlash('error', 'Impossible de supprimer l\'article. Token CSRF invalide.');
        
        return $this->redirectToRoute('app_article_show', ['id' => $article->getId()], Response::HTTP_SEE_OTHER);
    }

}
