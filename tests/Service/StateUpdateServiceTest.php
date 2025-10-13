<?php

namespace App\Tests\Service;

use App\Entity\Sortie;
use App\Enum\State;
use App\Repository\SortieRepository;
use App\Repository\UserRepository;
use App\Service\StateUpdateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StateUpdateServiceTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?StateUpdateService $stateUpdateService;
    private ?SortieRepository $sortieRepository;
    private ?UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();

        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->stateUpdateService = $container->get(StateUpdateService::class);
        $this->sortieRepository = $container->get(SortieRepository::class);
        $this->userRepository = $container->get(UserRepository::class);
    }

    /**
     * Teste qu'une sortie est archivée 1 mois après sa fin
     */
    public function testSortieIsArchivedOneMonthAfterEnd(): void
    {
        // Créer une sortie qui s'est terminée il y a plus d'un mois
        $sortie = $this->createTestSortie(
            startDateTime: new \DateTime('-2 months'),  // Début il y a 2 mois
            duration: 120,                               // Durée de 2 heures
            state: State::PASSED                         // État "Passée"
        );

        // La sortie devrait être archivée car elle s'est terminée il y a plus d'un mois
        // (début -2 mois + 2h de durée = fin il y a ~2 mois, donc > 1 mois)

        // Exécuter la mise à jour des états
        $updatedCount = $this->stateUpdateService->updateAllStates();

        // Vérifier qu'au moins une sortie a été mise à jour
        $this->assertGreaterThan(0, $updatedCount, 'Au moins une sortie devrait être mise à jour');

        // Recharger la sortie depuis la base de données
        $this->entityManager->clear();
        $sortie = $this->sortieRepository->find($sortie->getId());

        // Vérifier que la sortie est maintenant archivée
        $this->assertEquals(State::ARCHIVED, $sortie->getState(), 'La sortie devrait être archivée');
    }

    /**
     * Teste qu'une sortie N'EST PAS archivée avant 1 mois après sa fin
     */
    public function testSortieIsNotArchivedBeforeOneMonth(): void
    {
        // Créer une sortie qui s'est terminée il y a moins d'un mois
        $sortie = $this->createTestSortie(
            startDateTime: new \DateTime('-3 weeks'),   // Début il y a 3 semaines
            duration: 120,                               // Durée de 2 heures
            state: State::PASSED                         // État "Passée"
        );

        // La sortie NE devrait PAS être archivée car moins d'un mois s'est écoulé

        // Exécuter la mise à jour des états
        $this->stateUpdateService->updateAllStates();

        // Recharger la sortie depuis la base de données
        $this->entityManager->clear();
        $sortie = $this->sortieRepository->find($sortie->getId());

        // Vérifier que la sortie est toujours à l'état "Passée"
        $this->assertEquals(State::PASSED, $sortie->getState(), 'La sortie ne devrait pas encore être archivée');
        $this->assertNotEquals(State::ARCHIVED, $sortie->getState(), 'La sortie ne doit pas être archivée avant 1 mois');
    }

    /**
     * Teste qu'une sortie est archivée exactement 1 mois après sa fin
     */
    public function testSortieIsArchivedExactlyOneMonthAfterEnd(): void
    {
        // Créer une sortie qui s'est terminée il y a exactement 1 mois + 1 jour
        $startDate = new \DateTime('-1 month -1 day');

        $sortie = $this->createTestSortie(
            startDateTime: $startDate,
            duration: 60,                                // Durée de 1 heure
            state: State::PASSED
        );

        // Exécuter la mise à jour des états
        $updatedCount = $this->stateUpdateService->updateAllStates();

        // Vérifier qu'au moins une sortie a été mise à jour
        $this->assertGreaterThan(0, $updatedCount);

        // Recharger la sortie
        $this->entityManager->clear();
        $sortie = $this->sortieRepository->find($sortie->getId());

        // Vérifier que la sortie est archivée
        $this->assertEquals(State::ARCHIVED, $sortie->getState(), 'La sortie devrait être archivée après exactement 1 mois');
    }

    /**
     * Teste qu'une sortie passée devient archivée après le délai
     */
    public function testPassedSortieBecomeArchived(): void
    {
        // Créer une sortie qui vient de se terminer (état PASSED)
        $sortieRecent = $this->createTestSortie(
            startDateTime: new \DateTime('-1 day'),
            duration: 120,
            state: State::PASSED
        );

        // Créer une sortie terminée il y a plus d'un mois
        $sortieAncienne = $this->createTestSortie(
            startDateTime: new \DateTime('-2 months'),
            duration: 120,
            state: State::PASSED
        );

        // Exécuter la mise à jour
        $this->stateUpdateService->updateAllStates();

        // Recharger les sorties
        $this->entityManager->clear();
        $sortieRecent = $this->sortieRepository->find($sortieRecent->getId());
        $sortieAncienne = $this->sortieRepository->find($sortieAncienne->getId());

        // La sortie récente doit rester PASSED
        $this->assertEquals(State::PASSED, $sortieRecent->getState(), 'La sortie récente doit rester PASSED');

        // La sortie ancienne doit être ARCHIVED
        $this->assertEquals(State::ARCHIVED, $sortieAncienne->getState(), 'La sortie ancienne doit être ARCHIVED');
    }

    /**
     * Teste que les sorties annulées ne sont pas archivées
     */
    public function testCancelledSortiesAreNotArchived(): void
    {
        // Créer une sortie annulée il y a plus d'un mois
        $sortie = $this->createTestSortie(
            startDateTime: new \DateTime('-2 months'),
            duration: 120,
            state: State::CANCELLED
        );

        // Exécuter la mise à jour
        $this->stateUpdateService->updateAllStates();

        // Recharger la sortie
        $this->entityManager->clear();
        $sortie = $this->sortieRepository->find($sortie->getId());

        // Vérifier que la sortie reste CANCELLED et n'est pas ARCHIVED
        $this->assertEquals(State::CANCELLED, $sortie->getState(), 'Les sorties annulées ne doivent pas être archivées');
    }

    /**
     * Teste que plusieurs sorties sont archivées en une seule fois
     */
    public function testMultipleSortiesAreArchived(): void
    {
        // Créer plusieurs sorties anciennes
        $sortie1 = $this->createTestSortie(
            startDateTime: new \DateTime('-3 months'),
            duration: 120,
            state: State::PASSED
        );

        $sortie2 = $this->createTestSortie(
            startDateTime: new \DateTime('-2 months'),
            duration: 60,
            state: State::PASSED
        );

        $sortie3 = $this->createTestSortie(
            startDateTime: new \DateTime('-50 days'),
            duration: 180,
            state: State::PASSED
        );

        // Exécuter la mise à jour
        $updatedCount = $this->stateUpdateService->updateAllStates();

        // Au moins 3 sorties devraient être mises à jour
        $this->assertGreaterThanOrEqual(3, $updatedCount, 'Au moins 3 sorties devraient être archivées');

        // Recharger les sorties
        $this->entityManager->clear();
        $sortie1 = $this->sortieRepository->find($sortie1->getId());
        $sortie2 = $this->sortieRepository->find($sortie2->getId());
        $sortie3 = $this->sortieRepository->find($sortie3->getId());

        // Toutes devraient être archivées
        $this->assertEquals(State::ARCHIVED, $sortie1->getState());
        $this->assertEquals(State::ARCHIVED, $sortie2->getState());
        $this->assertEquals(State::ARCHIVED, $sortie3->getState());
    }

    /**
     * Teste qu'une sortie en cours ne peut pas être archivée
     */
    public function testInProgressSortieIsNotArchived(): void
    {
        // Créer une sortie actuellement en cours
        $sortie = $this->createTestSortie(
            startDateTime: new \DateTime('-30 minutes'),
            duration: 120,
            state: State::IN_PROGRESS
        );

        $originalState = $sortie->getState();

        // Exécuter la mise à jour
        $this->stateUpdateService->updateAllStates();

        // Recharger la sortie
        $this->entityManager->clear();
        $sortie = $this->sortieRepository->find($sortie->getId());

        // Vérifier que la sortie reste IN_PROGRESS
        $this->assertEquals(State::IN_PROGRESS, $sortie->getState(), 'Une sortie en cours ne doit pas être archivée');
    }

    /**
     * Méthode helper pour créer une sortie de test
     */
    private function createTestSortie(
        \DateTime $startDateTime,
        int $duration,
        State $state
    ): Sortie {
        $organizer = $this->userRepository->findOneByEmail('admin@test.com');

        // Calculer la date limite d'inscription (avant le début)
        $registrationDeadline = (clone $startDateTime)->modify('-1 day');

        $sortie = new Sortie();
        $sortie->setName('Sortie Test Archive - ' . uniqid());
        $sortie->setStartDateTime($startDateTime);
        $sortie->setRegistrationDeadline($registrationDeadline);
        $sortie->setDuration($duration);
        $sortie->setMaxRegistration(10);
        $sortie->setDescription('Sortie de test pour vérifier l\'archivage automatique');
        $sortie->setState($state);
        $sortie->setOrganisateur($organizer);
        $sortie->setSite($organizer->getSite());

        // Trouver un lieu existant
        $place = $this->entityManager->getRepository(\App\Entity\Place::class)->findOneBy([]);
        if ($place) {
            $sortie->setPlace($place);
        }

        $this->entityManager->persist($sortie);
        $this->entityManager->flush();

        return $sortie;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
        $this->stateUpdateService = null;
        $this->sortieRepository = null;
        $this->userRepository = null;
    }
}

