<?php

namespace App\Repository;

use App\Entity\MessageRead;
use App\Entity\User;
use App\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessageRead>
 */
class MessageReadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageRead::class);
    }

    /**
     * Compte le nombre de messages non lus pour un utilisateur dans une conversation
     */
    public function countUnreadMessages(Conversation $conversation, User $user): int
    {
        $em = $this->getEntityManager();

        return (int) $em->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from('App\Entity\Message', 'm')
            ->leftJoin('App\Entity\MessageRead', 'mr', 'WITH', 'mr.message = m.id AND mr.user = :user')
            ->where('m.conversation = :conversation')
            ->andWhere('m.sender != :user')
            ->andWhere('mr.id IS NULL')
            ->setParameter('conversation', $conversation)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte le nombre total de messages non lus pour un utilisateur
     */
    public function countAllUnreadMessages(User $user): int
    {
        $em = $this->getEntityManager();

        return (int) $em->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from('App\Entity\Message', 'm')
            ->leftJoin('App\Entity\MessageRead', 'mr', 'WITH', 'mr.message = m.id AND mr.user = :user')
            ->where('m.sender != :user')
            ->andWhere('mr.id IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
