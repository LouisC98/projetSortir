<?php

namespace App\Security\Voter;

use App\Entity\Sortie;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class SortieVoter extends Voter
{
    public const EDIT = 'SORTIE_EDIT';
    public const DELETE = 'SORTIE_DELETE';
    public const CANCEL = 'SORTIE_CANCEL';
    public const PUBLISH = 'SORTIE_PUBLISH';
    public const VIEW = 'SORTIE_VIEW';
    public const REGISTER = 'SORTIE_REGISTER';
    public const UNREGISTER = 'SORTIE_UNREGISTER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::EDIT,
            self::DELETE,
            self::CANCEL,
            self::PUBLISH,
            self::VIEW,
            self::REGISTER,
            self::UNREGISTER,
        ]) && $subject instanceof Sortie;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Sortie $sortie */
        $sortie = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($sortie, $user),
            self::EDIT => $this->canEdit($sortie, $user),
            self::DELETE => $this->canDelete($sortie, $user),
            self::CANCEL => $this->canCancel($sortie, $user),
            self::PUBLISH => $this->canPublish($sortie, $user),
            self::REGISTER => $this->canRegister($sortie, $user),
            self::UNREGISTER => $this->canUnregister($sortie, $user),
            default => false,
        };
    }

    private function canView(Sortie $sortie, User $user): bool
    {
        return true;
    }

    private function canEdit(Sortie $sortie, User $user): bool
    {
        $isOrganisateur = $sortie->getOrganisateur() === $user;
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        $editableStates = ['Créée', 'Ouverte'];
        $isEditableState = in_array($sortie->getState()->value, $editableStates, true);

        return ($isOrganisateur || $isAdmin) && $isEditableState;
    }

    private function canDelete(Sortie $sortie, User $user): bool
    {
        $isOrganisateur = $sortie->getOrganisateur() === $user;
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        $isCreatedState = $sortie->getState()->value === 'Créée';

        return ($isOrganisateur || $isAdmin) && $isCreatedState;
    }

    private function canCancel(Sortie $sortie, User $user): bool
    {
        $isOrganisateur = $sortie->getOrganisateur() === $user;
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        $cancellableStates = ['Créée', 'Ouverte'];
        $isCancellableState = in_array($sortie->getState()->value, $cancellableStates, true);

        return ($isOrganisateur || $isAdmin) && $isCancellableState;
    }

    private function canPublish(Sortie $sortie, User $user): bool
    {
        // Seul l'organisateur peut publier sa sortie
        // Et seulement si elle est dans l'état 'Créée'
        $isOrganisateur = $sortie->getOrganisateur() === $user;
        $isCreatedState = $sortie->getState()->value === 'Créée';

        return $isOrganisateur && $isCreatedState;
    }

    private function canRegister(Sortie $sortie, User $user): bool
    {
        // Un utilisateur peut s'inscrire si:
        // - Il n'est pas déjà inscrit
        // - Il n'est pas l'organisateur
        // - La sortie est dans l'état 'Ouverte'
        // - Il reste de la place
        // - La date limite d'inscription n'est pas dépassée
        $isAlreadyRegistered = $sortie->getParticipants()->contains($user);
        $isOrganisateur = $sortie->getOrganisateur() === $user;
        $isOpen = $sortie->getState()->value === 'Ouverte';
        $hasSpace = $sortie->getParticipants()->count() < $sortie->getMaxRegistration();
        $now = new \DateTime();
        $isBeforeDeadline = $sortie->getRegistrationDeadline() >= $now;

        return !$isAlreadyRegistered
            && !$isOrganisateur
            && $isOpen
            && $hasSpace
            && $isBeforeDeadline;
    }

    private function canUnregister(Sortie $sortie, User $user): bool
    {
        // Un utilisateur peut se désinscrire si:
        // - Il est inscrit
        // - La sortie est dans l'état 'Ouverte'
        // - La sortie n'a pas encore commencé
        $isRegistered = $sortie->getParticipants()->contains($user);
        $isOpen = $sortie->getState()->value === 'Ouverte';
        $now = new \DateTime();
        $hasNotStarted = $sortie->getStartDateTime() > $now;

        return $isRegistered && $isOpen && $hasNotStarted;
    }
}

