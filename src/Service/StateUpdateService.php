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
     * en fonction des dates et des règles métier
     */
    public function updateAllStates(): int
    {
        $now = new \DateTime();
        $updatedCount = 0;

        // Récupérer toutes les sorties non annulées, non passées définitivement et non archivées
        $sorties = $this->sortieRepository->createQueryBuilder('s')
            ->where('s.state != :cancelled')
            ->andWhere('s.state != :passed')
            ->andWhere('s.state != :archived')
            ->setParameter('cancelled', State::CANCELLED)
            ->setParameter('passed', State::PASSED)
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
     */
    private function calculateNewState(Sortie $sortie, \DateTime $now): State
    {
        $currentState = $sortie->getState();

        // Si la sortie est annulée ou archivée, on ne change pas son état
        if ($currentState === State::CANCELLED || $currentState === State::ARCHIVED) {
            return $currentState;
        }

        $registrationDeadline = $sortie->getRegistrationDeadline();
        $startDateTime = $sortie->getStartDateTime();

        // Calculer la date de fin de la sortie
        $endDateTime = (clone $startDateTime)->modify('+' . $sortie->getDuration() . ' minutes');

        // La sortie est archivée si la date de fin est dépassée + 1 mois
        $archiveDate = (clone $endDateTime)->modify('+1 month');
        if ($now >= $archiveDate) {
            return State::ARCHIVED;
        }

        // La sortie est passée si la date de fin est dépassée
        if ($now >= $endDateTime) {
            return State::PASSED;
        }

        // La sortie est en cours si on est entre la date de début et la date de fin
        if ($now >= $startDateTime && $now < $endDateTime) {
            return State::IN_PROGRESS;
        }

        // La sortie est clôturée si la date limite d'inscription est dépassée
        // mais que la sortie n'a pas encore commencé
        if ($now >= $registrationDeadline && $now < $startDateTime) {
            return State::CLOSED;
        }

        // Si la sortie est créée (non publiée), elle reste créée
        // L'organisateur doit la publier manuellement
        if ($currentState === State::CREATED && $now < $registrationDeadline) {
            return State::CREATED;
        }

        // Si la sortie est ouverte ou si elle devrait l'être (avant la date limite d'inscription)
        if ($now < $registrationDeadline) {
            return State::OPEN;
        }

        // Par défaut, retourner l'état actuel
        return $currentState;
    }

    /**
     * Met à jour le statut d'une sortie spécifique
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
