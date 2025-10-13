<?php

namespace App\Event;

use App\Entity\Sortie;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class SortieRegistrationEvent extends Event
{
    public const NAME = 'sortie.registration.event';

    public function __construct(
        private readonly Sortie $sortie,
        private readonly User $user,
        private readonly string $action
    ) {
    }

    public function getSortie(): Sortie
    {
        return $this->sortie;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAction(): string
    {
        return $this->action;
    }
}
