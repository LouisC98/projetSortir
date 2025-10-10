<?php
namespace App\Repository;

use App\Entity\ParticipantGroup;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParticipantGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticipantGroup::class);
    }

    public function save(ParticipantGroup $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ParticipantGroup $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** @return ParticipantGroup[] */
    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('g.updatedAt', 'DESC')
            ->addOrderBy('g.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return ParticipantGroup[] */
    public function searchByName(User $owner, string $q, int $limit = 20): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.owner = :owner')
            ->andWhere('LOWER(g.name) LIKE :q')
            ->setParameter('owner', $owner)
            ->setParameter('q', '%'.mb_strtolower($q).'%')
            ->orderBy('g.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByOwner(User $owner): int
    {
        return (int) $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->andWhere('g.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère tous les groupes accessibles par un utilisateur
     * (groupes dont il est propriétaire OU membre)
     *
     * @return ParticipantGroup[]
     */
    public function findAllAccessibleByUser(User $user): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.members', 'm')
            ->andWhere('g.owner = :user OR m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('g.updatedAt', 'DESC')
            ->addOrderBy('g.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
