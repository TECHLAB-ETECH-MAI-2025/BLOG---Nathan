<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Récupère les articles paginés d'une catégorie
     *
     * @param Category $category La catégorie
     * @param int $page Le numéro de page
     * @param int $limit Le nombre d'articles par page
     * @return array
     */
    public function findArticlesPaginated(Category $category, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        // Correction de la requête DQL
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('a') // Sélectionne l'entité Article
            ->from('App\Entity\Article', 'a') // Définit l'entité Article comme racine
            ->join('a.categories', 'c') // Joint avec les catégories
            ->where('c.id = :categoryId')
            ->setParameter('categoryId', $category->getId())
            ->orderBy('a.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total d'articles dans une catégorie
     *
     * @param Category $category La catégorie
     * @return int
     */
    public function countArticles(Category $category): int
    {
        // Correction de la requête DQL
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(a.id)')
            ->from('App\Entity\Article', 'a') // Définit l'entité Article comme racine
            ->join('a.categories', 'c') // Joint avec les catégories
            ->where('c.id = :categoryId')
            ->setParameter('categoryId', $category->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }


    //    /**
    //     * @return Category[] Returns an array of Category objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Category
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
