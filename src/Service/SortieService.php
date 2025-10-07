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
    public function cancel(Sortie $sortie, User $user, string $motif): void {

        $isOrganisateur = $user->getId() === $sortie->getOrganisateur();
        $isAdmin = in_array("ROLE_ADMIN", $user->getRoles());

        if (!$isOrganisateur && !$isAdmin) {
            throw new SortieException("Vous n'êtes pas autorisé à annuler cette sortie");
        }

        if ($sortie->getStartDateTime() < new \DateTime()) {
            throw new SortieException("La sortie a déjà commencé");
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
    public function createSortie(Sortie $sortie, User $user): void
    {
        // Vérification de la date limite d'inscription
        if ($sortie->getRegistrationDeadline() > $sortie->getStartDateTime()) {
            throw new SortieException("La date limite d'inscription doit être avant la date de début de la sortie !");
        }

        $sortie->setState(State::CREATED);
        $sortie->setSite($user->getSite());
        $sortie->setOrganisateur($user);
        $this->entityManager->persist($sortie);
        $this->entityManager->flush();
    }
}