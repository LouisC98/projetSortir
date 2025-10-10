<?php
namespace App\Entity;

use App\Repository\ParticipantGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipantGroupRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ParticipantGroup
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\OneToMany(mappedBy: 'group', targetEntity: ParticipantGroupMember::class, cascade: ['persist','remove'], orphanRemoval: true)]
    private Collection $members;

    #[ORM\Column(options: ['default' => true])]
    private bool $isPrivate = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->isPrivate = true;
    }

    #[ORM\PrePersist]
    public function onCreate(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getOwner(): User { return $this->owner; }
    public function setOwner(User $owner): self { $this->owner = $owner; return $this; }

    public function isPrivate(): bool { return $this->isPrivate; }
    public function setIsPrivate(bool $isPrivate): self { $this->isPrivate = $isPrivate; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function getMembers(): Collection { return $this->members; }

    public function addMember(ParticipantGroupMember $member): self
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setGroup($this);
        }
        return $this;
    }

    public function removeMember(ParticipantGroupMember $member): self
    {
        if ($this->members->removeElement($member)) {
            if ($member->getGroup() === $this) {
                $member->setGroup(null);
            }
        }
        return $this;
    }
}
