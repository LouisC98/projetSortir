<?php

namespace App\Service;

use App\Entity\Sortie;
use App\Entity\User;
use App\Enum\State;
use App\Exception\SortieException;
use Doctrine\ORM\EntityManagerInterface;

class SortieService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @throws SortieException
     */
    public function inscrire(Sortie $sortie, User $user): void
    {
        // Vérifications métier
        if ($sortie->getParticipants()->contains($user)) {
            throw new SortieException('Vous êtes déjà inscrit à cette sortie');
        }

        if ($sortie->getParticipants()->count() >= $sortie->getMaxRegistration()) {
            throw new SortieException('Cette sortie est complète');
        }

        if ($sortie->getRegistrationDeadline() < new \DateTime()) {
            throw new SortieException('La date limite d\'inscription est dépassée');
        }

        if ($sortie->getState() !== State::OPEN) {
            throw new SortieException('Cette sortie n\'est pas ouverte aux inscriptions');
        }

        // Inscription
        $sortie->addParticipant($user);
        $this->entityManager->flush();
    }

    /**
     * @throws SortieException
     */
    public function desinscrire(Sortie $sortie, User $user): void
    {
        if (!$sortie->getParticipants()->contains($user)) {
            throw new SortieException('Vous n\'êtes pas inscrit à cette sortie');
        }

        $now = new \DateTimeImmutable();
        if ($sortie->getStartDateTime() <= $now) {
            throw new SortieException('Impossible de se désister, la sortie a déjà commencé');
        }

        if (in_array($sortie->getState(), [State::IN_PROGRESS, State::PASSED, State::CANCELLED])) {
            throw new SortieException('Impossible de se désister : ' . $sortie->getState()->value);
        }

        // Désinscription
        $sortie->removeParticipant($user);
        $this->entityManager->flush();
    }

    /**
     * @throws SortieException
     */
    public function cancel(Sortie $sortie, User $user, string $motif): void {

        $isOrganisateur = $user->getId() === $sortie->getOrganisateur()->getId();
        $isAdmin = in_array("ROLE_ADMIN", $user->getRoles(), true);

        if (!$isOrganisateur && !$isAdmin) {
            throw new SortieException("Vous n'êtes pas autorisé à annuler cette sortie");
        }

        if (in_array($sortie->getState(), [State::PASSED, State::CANCELLED], true)) {
            throw new SortieException("Cette sortie ne peut plus être annulée");
        }

        $sortie->setState(State::CANCELLED);

        $description = $sortie->getDescription();
        $nouveauContenu = sprintf(
            "=== SORTIE ANNULÉE ===\nMotif : %s\n\n==========\n%s",
            $motif,
            $description
        );
        $sortie->setDescription($nouveauContenu);

        $this->entityManager->flush();
    }

    /**
     * @throws SortieException
     */
    public function createSortie(Sortie $sortie, User $user, bool $isActionPublish): void
    {
        if ($isActionPublish) {
            $sortie->setState(State::OPEN);
        } else {
            $sortie->setState(State::CREATED);
        }
        $sortie->setSite($user->getSite());
        $sortie->setOrganisateur($user);
        $this->entityManager->persist($sortie);
        $this->entityManager->flush();
    }

    /**
     * @throws SortieException
     */
    public function delete(Sortie $sortie, User $user): void {

        $isOrganisateur = $user->getId() === $sortie->getOrganisateur()->getId();
        $isAdmin = in_array("ROLE_ADMIN", $user->getRoles(), true);

        if (!$isOrganisateur && !$isAdmin) {
            throw new SortieException("Vous n'êtes pas autorisé à supprimer cette sortie");
        }

        $this->entityManager->remove($sortie);
        $this->entityManager->flush();
    }

    /**
     * @throws SortieException
     */
    public function publier(Sortie $sortie, User $user): void
    {
        if ($sortie->getOrganisateur()->getId() !== $user->getId()) {
            throw new SortieException("Vous n'êtes pas l'organisateur de la sortie");
        }

        $sortie->setState(State::OPEN);
        $this->entityManager->flush();
    }

}