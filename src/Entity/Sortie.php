<?php

namespace App\Entity;

use App\Enum\State;
use App\Repository\SortieRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SortieRepository::class)]
class Sortie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTime $startDateTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $duration = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $registrationDeadline = null;

    #[ORM\Column]
    private ?int $maxRegistration = null;

    #[ORM\Column(enumType: State::class)]
    private ?State $state = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getStartDateTime(): ?\DateTime
    {
        return $this->startDateTime;
    }

    public function setStartDateTime(\DateTime $startDateTime): static
    {
        $this->startDateTime = $startDateTime;

        return $this;
    }

    public function getDuration(): ?\DateTime
    {
        return $this->duration;
    }

    public function setDuration(\DateTime $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getRegistrationDeadline(): ?\DateTime
    {
        return $this->registrationDeadline;
    }

    public function setRegistrationDeadline(\DateTime $registrationDeadline): static
    {
        $this->registrationDeadline = $registrationDeadline;

        return $this;
    }

    public function getMaxRegistration(): ?int
    {
        return $this->maxRegistration;
    }

    public function setMaxRegistration(int $maxRegistration): static
    {
        $this->maxRegistration = $maxRegistration;

        return $this;
    }

    public function getState(): ?State
    {
        return $this->state;
    }

    public function setState(State $state): static
    {
        $this->state = $state;

        return $this;
    }
}
