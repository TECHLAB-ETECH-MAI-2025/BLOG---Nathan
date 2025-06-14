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
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/react_api')]
class ApiRestController extends AbstractController
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    #[Route('/articles', name: 'api_articles_rest', methods: ['GET'])]
    public function getArticles(ArticleRepository $articleRepository, Request $request): JsonResponse
    {
        // Support de la pagination pour React
        $page = max(1, $request->query->getInt('page', 1));
        $limit = $request->query->getInt('limit', 10);
        
        // Si c'est une requête DataTables (ancienne), on garde l'ancien format
        if ($request->query->has('draw')) {
            return $this->getArticlesForDataTables($articleRepository);
        }
        
        // Nouveau format pour React
        $articles = $articleRepository->findBy([], ['id' => 'DESC'], $limit, ($page - 1) * $limit);
        $total = $articleRepository->count([]);
        $maxPages = ceil($total / $limit);
        
        $articlesData = [];
        foreach ($articles as $article) {
            $articlesData[] = [
                'id' => $article->getId(),
                'title' => $article->getTitre(),
                'content' => $article->getContenu(),
                'excerpt' => $this->generateExcerpt($article->getContenu()),
                'author' => 'Admin', // Valeur par défaut
                'createdAt' => (new \DateTimeImmutable())->format('c'),
                'updatedAt' => (new \DateTimeImmutable())->format('c'),
                'commentsCount' => $article->getComments()->count(),
                'likesCount' => $article->getLikes()->count(),
                'categories' => $this->getArticleCategories($article)
            ];
        }
        
        return new JsonResponse([
            'articles' => $articlesData,
            'pagination' => [
                'currentPage' => $page,
                'maxPages' => $maxPages,
                'limit' => $limit,
                'total' => $total
            ]
        ]);
    }

    // Méthode pour maintenir la compatibilité avec DataTables
    private function getArticlesForDataTables(ArticleRepository $articleRepository): JsonResponse
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

    #[Route('/articles/{id}', name: 'api_article_show', methods: ['GET'])]
    public function getArticle(Article $article): JsonResponse
    {
        $comments = [];
        foreach ($article->getCommentsSortedByDate() as $comment) {
            $comments[] = [
                'id' => $comment->getId(),
                'author' => $comment->getAuteur(),
                'content' => $comment->getContenu(),
                'createdAt' => $comment->getCreatedAt()?->format('c')
            ];
        }

        return new JsonResponse([
            'id' => $article->getId(),
            'title' => $article->getTitre(),
            'content' => $article->getContenu(),
            'author' => 'Admin',
            'createdAt' => (new \DateTimeImmutable())->format('c'),
            'updatedAt' => (new \DateTimeImmutable())->format('c'),
            'comments' => $comments,
            'likesCount' => $article->getLikes()->count(),
            'categories' => $this->getArticleCategories($article)
        ]);
    }

    #[Route('/articles', name: 'api_article_create', methods: ['POST'])]
    public function createArticle(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $article = new Article();
        $article->setTitre($data['title'] ?? '');
        $article->setContenu($data['content'] ?? '');

        // Validation
        $errors = $this->validator->validate($article);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], 400);
        }

        $entityManager->persist($article);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $article->getId(),
            'title' => $article->getTitre(),
            'content' => $article->getContenu(),
            'author' => 'Admin',
            'createdAt' => (new \DateTimeImmutable())->format('c'),
            'updatedAt' => (new \DateTimeImmutable())->format('c'),
            'commentsCount' => 0,
            'likesCount' => 0
        ], 201);
    }

    #[Route('/comments/{id}', name: 'api_comments', methods: ['GET'])]
    public function getComments(Article $article): JsonResponse
    {
        $comments = [];
        foreach ($article->getComments() as $comment) {
            $comments[] = [
                'id' => $comment->getId(),
                'author' => $comment->getAuteur(), // Pour React
                'auteur' => $comment->getAuteur(), // Pour compatibilité
                'content' => $comment->getContenu(), // Pour React
                'contenu' => $comment->getContenu(), // Pour compatibilité
                'createdAt' => $comment->getCreatedAt()->format('c'), // Pour React
                'date' => $comment->getCreatedAt()->format('d/m/Y H:i') // Pour compatibilité
            ];
        }
        
        return new JsonResponse($comments);
    }

    #[Route('/comments', name: 'api_comment_create', methods: ['POST'])]
    public function createComment(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        // Récupérer l'article
        $article = $entityManager->getRepository(Article::class)->find($data['articleId'] ?? null);
        if (!$article) {
            return new JsonResponse(['error' => 'Article not found'], 404);
        }

        $comment = new Comment();
        $comment->setArticle($article);
        $comment->setAuteur($data['author'] ?? 'Anonyme');
        $comment->setContenu($data['content'] ?? '');
        $comment->setCreatedAt(new \DateTimeImmutable());

        // Validation
        $errors = $this->validator->validate($comment);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], 400);
        }

        $entityManager->persist($comment);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $comment->getId(),
            'author' => $comment->getAuteur(),
            'content' => $comment->getContenu(),
            'createdAt' => $comment->getCreatedAt()->format('c'),
            'articleId' => $article->getId()
        ], 201);
    }

    #[Route('/comments/{id}/add', name: 'api_comments_add', methods: ['POST'])]
    public function addComment(Request $request, Article $article, EntityManagerInterface $entityManager): JsonResponse
    {
        // Maintenir la compatibilité avec l'ancienne méthode
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
                'utilisateur' => $user
            ]);
            
            if ($like) {
                $entityManager->remove($like);
            }
        } else {
            // Ajouter un like
            $like = new Like();
            $like->setArticle($article);
            $like->setUtilisateur($user);
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

    private function generateExcerpt(string $content, int $length = 150): string
    {
        $plainText = strip_tags($content);
        if (strlen($plainText) <= $length) {
            return $plainText;
        }
        
        return substr($plainText, 0, $length) . '...';
    }

    private function getArticleCategories(Article $article): array
    {
        $categories = [];
        foreach ($article->getCategories() as $category) {
            $categories[] = [
                'id' => $category->getId(),
                'name' => $category->getNom()
            ];
        }
        return $categories;
    }
}
