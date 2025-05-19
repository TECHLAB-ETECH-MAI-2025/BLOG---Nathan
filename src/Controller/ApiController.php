<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Comment;
use App\Entity\Like;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/articles', name: 'api_articles', methods: ['GET'])]
    public function getArticles(ArticleRepository $articleRepository): JsonResponse
    {
        $articles = $articleRepository->findAll();
        
        $data = [];
        foreach ($articles as $article) {
            $categories = [];
            foreach ($article->getCategories() as $category) {
                $categories[] = '<span class="badge bg-secondary">' . $category->getNom() . '</span>';
            }
            
            $data[] = [
                'id' => $article->getId(),
                'titre' => $article->getTitre(),
                'contenu' => mb_substr($article->getContenu(), 0, 100) . '...',
                'categories' => implode(' ', $categories),
                'comments' => count($article->getComments()),
                'likes' => count($article->getLikes()),
                'actions' => $this->renderView('article/_actions.html.twig', [
                    'article' => $article
                ])
            ];
        }
        
        return new JsonResponse([
            'draw' => 1,
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data
        ]);
    }


    #[Route('/comments/{id}', name: 'api_comments', methods: ['GET'])]
    public function getComments(Article $article): JsonResponse
    {
        $comments = [];
        foreach ($article->getComments() as $comment) {
            $comments[] = [
                'id' => $comment->getId(),
                'auteur' => $comment->getAuteur(),
                'contenu' => $comment->getContenu(),
                'date' => $comment->getCreatedAt()->format('d/m/Y H:i')
            ];
        }
        
        return new JsonResponse($comments);
    }

    #[Route('/comments/{id}/add', name: 'api_comments_add', methods: ['POST'])]
    public function addComment(Request $request, Article $article, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $comment = new Comment();
        $comment->setArticle($article);
        $comment->setAuteur($data['auteur'] ?? ($this->getUser() ? $this->getUser()->getEmail() : 'Anonyme'));
        $comment->setContenu($data['contenu']);
        $comment->setCreatedAt(new \DateTimeImmutable());
        
        $entityManager->persist($comment);
        $entityManager->flush();
        
        return new JsonResponse([
            'id' => $comment->getId(),
            'auteur' => $comment->getAuteur(),
            'contenu' => $comment->getContenu(),
            'date' => $comment->getCreatedAt()->format('d/m/Y H:i')
        ]);
    }

    #[Route('/likes/{id}', name: 'api_likes_count', methods: ['GET'])]
    public function getLikesCount(Article $article): JsonResponse
    {
        return new JsonResponse([
            'count' => count($article->getLikes()),
            'liked' => $this->getUser() ? $article->isLikedByUser($this->getUser()) : false
        ]);
    }

    #[Route('/likes/{id}/toggle', name: 'api_likes_toggle', methods: ['POST'])]
    public function toggleLike(Article $article, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a déjà aimé l'article
        $liked = $article->isLikedByUser($user);
        
        if ($liked) {
            // Supprimer le like
            $like = $entityManager->getRepository(Like::class)->findOneBy([
                'article' => $article,
                'utilisateur' => $user  // Assurez-vous que ce nom de propriété est correct
            ]);
            
            if ($like) {
                $entityManager->remove($like);
            }
        } else {
            // Ajouter un like
            $like = new Like();
            $like->setArticle($article);
            $like->setUtilisateur($user);  // Assurez-vous que cette méthode existe
            $like->setCreatedAt(new \DateTimeImmutable());
            
            $entityManager->persist($like);
        }
        
        $entityManager->flush();

        $entityManager->refresh($article);
        
        return new JsonResponse([
            'count' => count($article->getLikes()),
            'liked' => !$liked
        ]);
    }


    #[Route('/search', name: 'api_search', methods: ['GET'])]
    public function search(Request $request, ArticleRepository $articleRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        if (strlen($query) < 2) {
            return new JsonResponse([]);
        }
        
        $articles = $articleRepository->searchByTerm($query);
        
        $results = [];
        foreach ($articles as $article) {
            $results[] = [
                'id' => $article->getId(),
                'titre' => $article->getTitre(),
                'url' => $this->generateUrl('app_article_show', ['id' => $article->getId()])
            ];
        }
        
        return new JsonResponse($results);
    }


}
