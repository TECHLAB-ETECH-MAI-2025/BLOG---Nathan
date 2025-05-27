<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Récupère la conversation entre deux utilisateurs
    */
    public function findConversation(User $user1, User $user2, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.expediteur = :user1 AND m.destinataire = :user2) OR (m.expediteur = :user2 AND m.destinataire = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les nouveaux messages depuis une date donnée
    */
    public function findNewMessages(User $user1, User $user2, \DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.expediteur = :user1 AND m.destinataire = :user2) OR (m.expediteur = :user2 AND m.destinataire = :user1)')
            ->andWhere('m.createdAt > :since')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->setParameter('since', $since)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère la liste des conversations d'un utilisateur
    */
    public function findUserConversations(User $user): array
    {
        $qb = $this->createQueryBuilder('m')
            ->select('DISTINCT IDENTITY(m.expediteur) as expediteur_id, IDENTITY(m.destinataire) as destinataire_id, MAX(m.createdAt) as last_message_date')
            ->where('m.expediteur = :user OR m.destinataire = :user')
            ->setParameter('user', $user)
            ->groupBy('expediteur_id, destinataire_id')
            ->orderBy('last_message_date', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Marque les messages comme lus
    */
    public function markAsRead(User $expediteur, User $destinataire): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', ':isRead')
            ->where('m.expediteur = :expediteur AND m.destinataire = :destinataire AND m.isRead = false')
            ->setParameter('isRead', true)
            ->setParameter('expediteur', $expediteur)
            ->setParameter('destinataire', $destinataire)
            ->getQuery()
            ->execute();
    }

    /**
     * Compte les messages non lus pour un utilisateur
    */
    public function countUnreadMessages(User $user): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.destinataire = :user AND m.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
