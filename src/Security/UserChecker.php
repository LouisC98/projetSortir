<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Vérifie si le compte est inactif
        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte est inactif. Veuillez contacter l’administrateur.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
