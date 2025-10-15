<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * Trouve toutes les conversations d'un utilisateur
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.participants', 'p')
            ->where('p = :user')
            ->setParameter('user', $user)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une conversation privée entre deux utilisateurs
     */
    public function findPrivateConversation(User $user1, User $user2): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.participants', 'p1')
            ->innerJoin('c.participants', 'p2')
            ->where('c.type = :type')
            ->andWhere('p1 = :user1')
            ->andWhere('p2 = :user2')
            ->setParameter('type', 'private')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve ou crée une conversation privée entre deux utilisateurs
     */
    public function findOrCreatePrivateConversation(User $user1, User $user2): Conversation
    {
        $conversation = $this->findPrivateConversation($user1, $user2);

        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->setType('private');
            $conversation->addParticipant($user1);
            $conversation->addParticipant($user2);

            $this->getEntityManager()->persist($conversation);
            $this->getEntityManager()->flush();
        }

        return $conversation;
    }
}

