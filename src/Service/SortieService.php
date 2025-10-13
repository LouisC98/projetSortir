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
     * Inscrit un utilisateur à une sortie
     *
     * Vérifie que l'utilisateur n'est pas déjà inscrit, que la sortie n'est pas complète,
     * que la date limite d'inscription n'est pas dépassée et que la sortie est ouverte.
     *
     * @param Sortie $sortie La sortie concernée
     * @param User $user L'utilisateur à inscrire
     *
     * @return void
     *
     * @throws SortieException Si l'utilisateur est déjà inscrit, la sortie est complète,
     *                         la date limite est dépassée ou la sortie n'est pas ouverte
     */
    public function inscrire(Sortie $sortie, User $user): void
    {
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

        $sortie->addParticipant($user);
        $this->entityManager->flush();
    }

    /**
     * Désinscrit un utilisateur d'une sortie
     *
     * Vérifie que l'utilisateur est bien inscrit et que la sortie n'a pas encore commencé
     * avant de procéder à la désinscription.
     *
     * @param Sortie $sortie La sortie concernée
     * @param User $user L'utilisateur à désinscrire
     *
     * @return void
     *
     * @throws SortieException Si l'utilisateur n'est pas inscrit, la sortie a déjà commencé
     *                         ou son état ne permet pas la désinscription
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

        $sortie->removeParticipant($user);
        $this->entityManager->flush();
    }

    /**
     * Annule une sortie avec un motif
     *
     * Vérifie que l'utilisateur est l'organisateur ou un administrateur avant d'annuler
     * la sortie. Le motif d'annulation est ajouté à la description.
     *
     * @param Sortie $sortie La sortie à annuler
     * @param User $user L'utilisateur demandant l'annulation
     * @param string $motif Le motif de l'annulation
     *
     * @return void
     *
     * @throws SortieException Si l'utilisateur n'est pas autorisé ou si la sortie
     *                         est déjà passée ou annulée
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
     * Crée une nouvelle sortie
     *
     * Initialise la sortie avec le bon état (CRÉÉE ou OUVERTE selon l'action),
     * associe le site de l'organisateur et persiste en base de données.
     *
     * @param Sortie $sortie La sortie à créer
     * @param User $user L'utilisateur organisateur
     * @param bool $isActionPublish True pour publier directement, false pour garder en brouillon
     *
     * @return void
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
     * Supprime une sortie
     *
     * Vérifie que l'utilisateur est l'organisateur ou un administrateur
     * avant de supprimer définitivement la sortie.
     *
     * @param Sortie $sortie La sortie à supprimer
     * @param User $user L'utilisateur demandant la suppression
     *
     * @return void
     *
     * @throws SortieException Si l'utilisateur n'est pas autorisé à supprimer cette sortie
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
     * Publie une sortie en brouillon
     *
     * Change l'état d'une sortie de CRÉÉE à OUVERTE pour la rendre visible
     * et accessible aux inscriptions.
     *
     * @param Sortie $sortie La sortie à publier
     * @param User $user L'utilisateur demandant la publication
     *
     * @return void
     *
     * @throws SortieException Si l'utilisateur n'est pas l'organisateur
     */
    public function publier(Sortie $sortie, User $user): void
    {
        if ($sortie->getOrganisateur()->getId() !== $user->getId()) {
            throw new SortieException("Vous n'êtes pas l'organisateur de la sortie");
        }

        $sortie->setState(State::OPEN);
        $this->entityManager->flush();
    }

    /**
     * Modifie une sortie existante
     *
     * Vérifie que l'utilisateur est l'organisateur et que la sortie est dans un état
     * modifiable (CRÉÉE, OUVERTE ou CLÔTURÉE) avant de persister les modifications.
     *
     * @param Sortie $sortie La sortie à modifier
     * @param User $user L'utilisateur demandant la modification
     *
     * @return void
     *
     * @throws SortieException Si l'utilisateur n'est pas l'organisateur ou si l'état
     *                         de la sortie ne permet pas la modification
     */
    public function edit(Sortie $sortie, User $user): void
    {
        if ($sortie->getOrganisateur()->getId() !== $user->getId()) {
            throw new SortieException("Vous n'êtes pas l'organisateur de la sortie");
        }

        if (!in_array($sortie->getState(), [State::CREATED, State::OPEN, State::CLOSED], true)) {
            throw new SortieException("Vous ne pouvez plus modifier cette sortie (activité en cours, passée, annulée ou archivée)");
        }

        $this->entityManager->flush();
    }
}