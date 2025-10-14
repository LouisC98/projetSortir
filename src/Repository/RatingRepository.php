<?php

namespace App\Repository;

use App\Entity\Rating;
use App\Entity\Sortie;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rating>
 */
class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    /**
     * Calcule la moyenne des notes pour une sortie
     */
    public function getAverageRating(Sortie $sortie): ?float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.score) as average')
            ->where('r.sortie = :sortie')
            ->setParameter('sortie', $sortie)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round((float)$result, 1) : null;
    }

    /**
     * Compte le nombre de notes pour une sortie
     */
    public function countRatings(Sortie $sortie): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.sortie = :sortie')
            ->setParameter('sortie', $sortie)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vérifie si un utilisateur a déjà noté une sortie
     */
    public function hasUserRated(User $user, Sortie $sortie): bool
    {
        $count = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user = :user')
            ->andWhere('r.sortie = :sortie')
            ->setParameter('user', $user)
            ->setParameter('sortie', $sortie)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Récupère la note d'un utilisateur pour une sortie
     */
    public function getUserRating(User $user, Sortie $sortie): ?Rating
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.sortie = :sortie')
            ->setParameter('user', $user)
            ->setParameter('sortie', $sortie)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère toutes les notes d'une sortie avec les utilisateurs
     */
    public function findBySortieWithUsers(Sortie $sortie): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')
            ->addSelect('u')
            ->where('r.sortie = :sortie')
            ->setParameter('sortie', $sortie)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

