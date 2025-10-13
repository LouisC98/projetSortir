<?php

namespace App\Service;

use App\Entity\Sortie;
use App\Enum\State;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;

class StateUpdateService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SortieRepository $sortieRepository
    ) {
    }

    /**
     * Met à jour automatiquement les statuts de toutes les sorties
     *
     * Parcourt toutes les sorties non annulées et non archivées
     * pour mettre à jour leur état en fonction des dates actuelles.
     *
     * @return int Le nombre de sorties mises à jour
     */
    public function updateAllStates(): int
    {
        $now = new \DateTime();
        $updatedCount = 0;

        $sorties = $this->sortieRepository->createQueryBuilder('s')
            ->where('s.state != :cancelled')
            ->andWhere('s.state != :archived')
            ->setParameter('cancelled', State::CANCELLED)
            ->setParameter('archived', State::ARCHIVED)
            ->getQuery()
            ->getResult();

        foreach ($sorties as $sortie) {
            $oldState = $sortie->getState();
            $newState = $this->calculateNewState($sortie, $now);

            if ($oldState !== $newState) {
                $sortie->setState($newState);
                $updatedCount++;
            }
        }

        if ($updatedCount > 0) {
            $this->entityManager->flush();
        }

        return $updatedCount;
    }

    /**
     * Calcule le nouvel état d'une sortie en fonction de ses dates
     *
     * Applique les règles métier pour déterminer l'état approprié d'une sortie
     * selon la date actuelle et les différentes dates de la sortie (deadline, début, fin, archivage).
     *
     * @param Sortie $sortie La sortie à évaluer
     * @param \DateTime $now La date/heure de référence
     *
     * @return State Le nouvel état calculé
     */
    private function calculateNewState(Sortie $sortie, \DateTime $now): State
    {
        $currentState = $sortie->getState();

        if ($currentState === State::CANCELLED || $currentState === State::ARCHIVED) {
            return $currentState;
        }

        $registrationDeadline = $sortie->getRegistrationDeadline();
        $startDateTime = $sortie->getStartDateTime();

        $endDateTime = (clone $startDateTime)->modify('+' . $sortie->getDuration() . ' minutes');

        $archiveDate = (clone $endDateTime)->modify('+1 month');
        if ($now >= $archiveDate) {
            return State::ARCHIVED;
        }

        if ($now >= $endDateTime) {
            return State::PASSED;
        }

        if ($now >= $startDateTime && $now < $endDateTime) {
            return State::IN_PROGRESS;
        }

        $nowDate = $now->format('Y-m-d');
        $deadlineDate = $registrationDeadline->format('Y-m-d');

        if ($nowDate > $deadlineDate && $now < $startDateTime) {
            return State::CLOSED;
        }

        if ($currentState === State::CREATED && $now < $registrationDeadline) {
            return State::CREATED;
        }

        if ($now < $registrationDeadline) {
            return State::OPEN;
        }

        return $currentState;
    }

    /**
     * Met à jour le statut d'une sortie spécifique
     *
     * Vérifie et met à jour l'état d'une seule sortie si nécessaire.
     *
     * @param Sortie $sortie La sortie à mettre à jour
     *
     * @return bool True si l'état a changé, false sinon
     */
    public function updateSortieState(Sortie $sortie): bool
    {
        $now = new \DateTime();
        $oldState = $sortie->getState();
        $newState = $this->calculateNewState($sortie, $now);

        if ($oldState !== $newState) {
            $sortie->setState($newState);
            $this->entityManager->flush();
            return true;
        }

        return false;
    }
}
