<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Récupère les articles paginés
     *
     * @param int $page Le numéro de page
     * @param int $limit Le nombre d'articles par page
     * @return array
     */
    public function findPaginated(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('a')
            ->orderBy('a.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total d'articles
     *
     * @return int
     */
    public function countAll(): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les articles pour DataTables avec filtrage, tri et pagination
     */
    public function findForDataTable(int $start, int $length, ?string $search, string $orderBy, string $orderDir): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.categories', 'c')
            ->leftJoin('a.comments', 'co')
            ->leftJoin('a.likes', 'l');
        
        // Appliquer la recherche
        if ($search) {
            $qb->andWhere('a.titre LIKE :search OR a.contenu LIKE :search OR c.nom LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        // Compter le nombre total d'articles
        $totalQb = clone $qb;
        $totalCount = $totalQb->select('COUNT(DISTINCT a.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        // Appliquer le tri
        if ($orderBy) {
            $qb->orderBy($orderBy, $orderDir);
        }
        
        // Appliquer la pagination
        $qb->setFirstResult($start)
           ->setMaxResults($length)
           ->groupBy('a.id');
        
        // Récupérer les résultats
        $articles = $qb->getQuery()->getResult();
        
        // Compter le nombre d'articles filtrés
        $filteredCount = $totalCount;
        if ($search) {
            $filteredQb = $this->createQueryBuilder('a')
                ->select('COUNT(DISTINCT a.id)')
                ->leftJoin('a.categories', 'c')
                ->andWhere('a.titre LIKE :search OR a.contenu LIKE :search OR c.nom LIKE :search')
                ->setParameter('search', '%' . $search . '%');
            
            $filteredCount = $filteredQb->getQuery()->getSingleScalarResult();
        }
        
        return [
            'data' => $articles,
            'totalCount' => $totalCount,
            'filteredCount' => $filteredCount
        ];
    }

    /**
     * Recherche des articles par terme
     */
    public function searchByTerm(string $term): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.titre LIKE :term')
            ->orWhere('a.contenu LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }




    //    /**
    //     * @return Article[] Returns an array of Article objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Article
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
