<?php
namespace App\Repository;

use App\Entity\ParticipantGroup;
use App\Entity\ParticipantGroupMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParticipantGroupMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticipantGroupMember::class);
    }

    public function save(ParticipantGroupMember $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ParticipantGroupMember $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** @return ParticipantGroupMember[] */
    public function findByGroup(ParticipantGroup $group): array
    {
        return $this->createQueryBuilder('gm')
            ->andWhere('gm.group = :group')
            ->setParameter('group', $group)
            ->orderBy('gm.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function isUserInGroup(ParticipantGroup $group, User $user): bool
    {
        $count = (int) $this->createQueryBuilder('gm')
            ->select('COUNT(gm.id)')
            ->andWhere('gm.group = :group')
            ->andWhere('gm.user = :user')
            ->setParameter('group', $group)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function countMembers(ParticipantGroup $group): int
    {
        return (int) $this->createQueryBuilder('gm')
            ->select('COUNT(gm.id)')
            ->andWhere('gm.group = :group')
            ->setParameter('group', $group)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return ParticipantGroup[] */
    public function findGroupsForUser(User $member, User $owner): array
    {
        return $this->createQueryBuilder('gm')
            ->select('g')
            ->join('gm.group', 'g')
            ->andWhere('gm.user = :member')
            ->andWhere('g.owner = :owner')
            ->setParameter('member', $member)
            ->setParameter('owner', $owner)
            ->orderBy('g.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
