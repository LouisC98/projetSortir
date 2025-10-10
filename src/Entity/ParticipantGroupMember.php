<?php
namespace App\Entity;

use App\Repository\ParticipantGroupMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ParticipantGroupMemberRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_group_user', columns: ['group_id','user_id'])]
#[UniqueEntity(fields: ['group','user'], message: 'Ce participant est déjà dans le groupe.')]
class ParticipantGroupMember
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ParticipantGroup::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ParticipantGroup $group = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column]
    private \DateTimeImmutable $addedAt;

    public function __construct()
    {
        $this->addedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getGroup(): ?ParticipantGroup { return $this->group; }
    public function setGroup(?ParticipantGroup $group): self { $this->group = $group; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getAddedAt(): \DateTimeImmutable { return $this->addedAt; }
}
