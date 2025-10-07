<?php

namespace App\Repository;

use App\Entity\Sortie;
use App\Entity\User;
use App\Enum\State;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sortie>
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    //    /**
    //     * @return Sortie[] Returns an array of Sortie objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Sortie
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Recherche des sorties avec filtres
     */
    public function findWithFilters(array $filters, ?User $user = null, int $limit = 10, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.participants', 'p')
            ->leftJoin('s.site', 'site')
            ->leftJoin('s.organisateur', 'o');

        // Filtre par site
        if (isset($filters['site']) && $filters['site']) {
            $qb->andWhere('s.site = :site')
               ->setParameter('site', $filters['site']);
        }

        // Filtre par nom
        if (isset($filters['name']) && $filters['name']) {
            $qb->andWhere('s.name LIKE :name')
               ->setParameter('name', '%' . $filters['name'] . '%');
        }

        // Filtre par date de début
        if (isset($filters['dateDebut']) && $filters['dateDebut']) {
            $qb->andWhere('s.startDateTime >= :dateDebut')
               ->setParameter('dateDebut', $filters['dateDebut']);
        }

        // Filtre par date de fin
        if (isset($filters['dateFin']) && $filters['dateFin']) {
            $dateFin = clone $filters['dateFin'];
            $dateFin->setTime(23, 59, 59); // Inclure toute la journée
            $qb->andWhere('s.startDateTime <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }

        // Gestion des filtres utilisateur - uniquement si un utilisateur est connecté
        if ($user) {
            $userConditions = [];
            $hasUserFilters = false;

            // Mes sorties organisées
            if (isset($filters['mesSorties']) && $filters['mesSorties']) {
                $userConditions[] = 's.organisateur = :user_org';
                $qb->setParameter('user_org', $user);
                $hasUserFilters = true;
            }

            // Sorties où je suis inscrit
            if (isset($filters['sortiesInscrit']) && $filters['sortiesInscrit']) {
                $userConditions[] = ':user_participant MEMBER OF s.participants';
                $qb->setParameter('user_participant', $user);
                $hasUserFilters = true;
            }

            // Sorties où je ne suis pas inscrit
            if (isset($filters['sortiesNonInscrit']) && $filters['sortiesNonInscrit']) {
                $userConditions[] = ':user_not_participant NOT MEMBER OF s.participants AND s.organisateur != :user_not_org';
                $qb->setParameter('user_not_participant', $user);
                $qb->setParameter('user_not_org', $user);
                $hasUserFilters = true;
            }

            // Appliquer les conditions utilisateur avec OR si au moins un filtre utilisateur est sélectionné
            if ($hasUserFilters && !empty($userConditions)) {
                $qb->andWhere('(' . implode(' OR ', $userConditions) . ')');
            }
        }

        // Filtre sorties passées
        if (isset($filters['sortiesPassees']) && $filters['sortiesPassees']) {
            // Afficher UNIQUEMENT les sorties passées (par date ou par état)
            $now = new \DateTime();
            $qb->andWhere('(s.startDateTime < :now OR s.state = :passedState)')
               ->setParameter('now', $now)
               ->setParameter('passedState', State::PASSED->value);
        } else {
            // Par défaut, ne pas afficher les sorties passées et annulées
            $now = new \DateTime();
            $qb->andWhere('(s.startDateTime >= :now OR s.state IN (:activeStates))')
               ->setParameter('now', $now)
               ->setParameter('activeStates', [
                   State::OPEN->value,
                   State::CLOSED->value,
                   State::IN_PROGRESS->value
               ]);
        }

        // Éviter les doublons dus aux jointures
        $qb->distinct();

        return $qb->orderBy('s.startDateTime', 'DESC')
                  ->setMaxResults($limit)
                  ->setFirstResult($offset)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Compte le nombre de sorties avec filtres
     */
    public function countWithFilters(array $filters, ?User $user = null): int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(DISTINCT s.id)')
            ->leftJoin('s.participants', 'p')
            ->leftJoin('s.site', 'site')
            ->leftJoin('s.organisateur', 'o');

        // Filtre par site
        if (isset($filters['site']) && $filters['site']) {
            $qb->andWhere('s.site = :site')
               ->setParameter('site', $filters['site']);
        }

        // Filtre par nom
        if (isset($filters['name']) && $filters['name']) {
            $qb->andWhere('s.name LIKE :name')
               ->setParameter('name', '%' . $filters['name'] . '%');
        }

        // Filtre par date de début
        if (isset($filters['dateDebut']) && $filters['dateDebut']) {
            $qb->andWhere('s.startDateTime >= :dateDebut')
               ->setParameter('dateDebut', $filters['dateDebut']);
        }

        // Filtre par date de fin
        if (isset($filters['dateFin']) && $filters['dateFin']) {
            $dateFin = clone $filters['dateFin'];
            $dateFin->setTime(23, 59, 59);
            $qb->andWhere('s.startDateTime <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }

        // Gestion des filtres utilisateur - uniquement si un utilisateur est connecté
        if ($user) {
            $userConditions = [];
            $hasUserFilters = false;

            // Mes sorties organisées
            if (isset($filters['mesSorties']) && $filters['mesSorties']) {
                $userConditions[] = 's.organisateur = :user_org';
                $qb->setParameter('user_org', $user);
                $hasUserFilters = true;
            }

            // Sorties où je suis inscrit
            if (isset($filters['sortiesInscrit']) && $filters['sortiesInscrit']) {
                $userConditions[] = ':user_participant MEMBER OF s.participants';
                $qb->setParameter('user_participant', $user);
                $hasUserFilters = true;
            }

            // Sorties où je ne suis pas inscrit
            if (isset($filters['sortiesNonInscrit']) && $filters['sortiesNonInscrit']) {
                $userConditions[] = ':user_not_participant NOT MEMBER OF s.participants AND s.organisateur != :user_not_org';
                $qb->setParameter('user_not_participant', $user);
                $qb->setParameter('user_not_org', $user);
                $hasUserFilters = true;
            }

            // Appliquer les conditions utilisateur avec OR si au moins un filtre utilisateur est sélectionné
            if ($hasUserFilters && !empty($userConditions)) {
                $qb->andWhere('(' . implode(' OR ', $userConditions) . ')');
            }
        }

        // Filtre sorties passées
        if (isset($filters['sortiesPassees']) && $filters['sortiesPassees']) {
            // Afficher UNIQUEMENT les sorties passées (par date ou par état)
            $now = new \DateTime();
            $qb->andWhere('(s.startDateTime < :now OR s.state = :passedState)')
               ->setParameter('now', $now)
               ->setParameter('passedState', State::PASSED->value);
        } else {
            // Par défaut, ne pas afficher les sorties passées et annulées
            $now = new \DateTime();
            $qb->andWhere('(s.startDateTime >= :now OR s.state IN (:activeStates))')
               ->setParameter('now', $now)
               ->setParameter('activeStates', [
                   State::OPEN->value,
                   State::CLOSED->value,
                   State::IN_PROGRESS->value
               ]);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
