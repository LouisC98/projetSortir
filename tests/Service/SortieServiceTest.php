<?php

namespace App\Tests\Service;

use App\Entity\Sortie;
use App\Entity\User;
use App\Enum\State;
use App\Exception\SortieException;
use App\Service\SortieService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SortieServiceTest extends TestCase
{
    private SortieService $sortieService;
    private MockObject|EntityManagerInterface $entityManagerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        $this->sortieService = new SortieService($this->entityManagerMock);
    }

    /**
     * Teste l'inscription réussie d'un utilisateur à une sortie.
     */
    public function testInscrireSuccess(): void
    {
        // --- Préparation des données ---
        $sortie = new Sortie();
        $sortie->setMaxRegistration(10);
        $sortie->setRegistrationDeadline(new \DateTime('+1 day'));
        $sortie->setState(State::OPEN);

        $user = new User();

        $this->entityManagerMock->expects($this->once())
                                ->method('flush');

        // --- Exécution de la méthode à tester ---
        $this->sortieService->inscrire($sortie, $user);

        // --- Vérifications ---
        $this->assertTrue($sortie->getParticipants()->contains($user));
    }

    /**
     * Teste l'inscription d'un utilisateur déjà inscrit.
     */
    public function testInscrireAlreadyRegistered(): void
    {
        // --- Préparation des données ---
        $sortie = new Sortie();
        $sortie->setMaxRegistration(10);
        $sortie->setRegistrationDeadline(new \DateTime('+1 day'));
        $sortie->setState(State::OPEN);

        $user = new User();
        $sortie->addParticipant($user);

        // On s'attend à ce qu'aucune exception ne soit levée par le service
        $this->expectException(SortieException::class);
        $this->expectExceptionMessage('Vous êtes déjà inscrit à cette sortie');

        $this->entityManagerMock->expects($this->never())
                                ->method('flush');

        // --- Exécution de la méthode à tester ---
        $this->sortieService->inscrire($sortie, $user);
    }

    /**
     * Teste l'inscription à une sortie complète.
     */
    public function testInscrireSortieFull(): void
    {
        // --- Préparation des données ---
        $sortie = new Sortie();
        $sortie->setMaxRegistration(1);
        $sortie->setRegistrationDeadline(new \DateTime('+1 day'));
        $sortie->setState(State::OPEN);

        // On ajoute un participant pour que la sortie soit pleine
        $existingUser = new User();
        $sortie->addParticipant($existingUser);

        $newUser = new User();

        $this->expectException(SortieException::class);
        $this->expectExceptionMessage('Cette sortie est complète');
        $this->entityManagerMock->expects($this->never())
                                ->method('flush');

        // --- Exécution de la méthode à tester ---
        $this->sortieService->inscrire($sortie, $newUser);
    }

    /**
     * Teste l'inscription après la date limite.
     */
    public function testInscrireDeadlinePassed(): void
    {
        // --- Préparation des données ---
        $sortie = new Sortie();
        $sortie->setMaxRegistration(10);
        $sortie->setRegistrationDeadline(new \DateTime('-1 day'));
        $sortie->setState(State::OPEN);

        $user = new User();

        $this->expectException(SortieException::class);
        $this->expectExceptionMessage('La date limite d\'inscription est dépassée');
        $this->entityManagerMock->expects($this->never())
                                ->method('flush');

        // --- Exécution de la méthode à tester ---
        $this->sortieService->inscrire($sortie, $user);
    }

    /**
     * Teste l'inscription à une sortie non ouverte.
     */
    public function testInscrireSortieNotOpen(): void
    {
        // --- Préparation des données ---
        $sortie = new Sortie();
        $sortie->setMaxRegistration(10);
        $sortie->setRegistrationDeadline(new \DateTime('+1 day'));
        $sortie->setState(State::CREATED);

        $user = new User();

        $this->expectException(SortieException::class);
        $this->expectExceptionMessage('Cette sortie n\'est pas ouverte aux inscriptions');
        $this->entityManagerMock->expects($this->never())
                                ->method('flush');

        // --- Exécution de la méthode à tester ---
        $this->sortieService->inscrire($sortie, $user);
    }

    /**
     * Teste la désinscription réussie d'un utilisateur d'une sortie.
     */
    public function testDesinscrireSuccess(): void
    {
        // --- Préparation des données ---
        $sortie = new Sortie();
        $sortie->setStartDateTime(new \DateTime('+1 day'));
        $user = new User();
        $sortie->addParticipant($user);

        $this->entityManagerMock->expects($this->once())
                                ->method('flush');

        // --- Exécution de la méthode à tester ---
        $this->sortieService->desinscrire($sortie, $user);

        // --- Vérifications ---
        $this->assertFalse($sortie->getParticipants()->contains($user));
    }

    /**
     * Teste la désinscription d'un utilisateur qui n'est pas participant.
     */
    public function testDesinscrireNotParticipant(): void
    {
        // --- Préparation des données ---
        $sortie = new Sortie();
        $sortie->setStartDateTime(new \DateTime('+1 day'));
        $user = new User();

        $this->expectException(SortieException::class);
        $this->expectExceptionMessage('Vous n\'êtes pas inscrit à cette sortie');
        $this->entityManagerMock->expects($this->never())
                                ->method('flush');

        // --- Exécution de la méthode à tester ---
        $this->sortieService->desinscrire($sortie, $user);
    }

    /**
     * Teste la désinscription d'une sortie qui a déjà commencé.
     */
    public function testDesinscrireSortieAlreadyStarted(): void
    {
        // --- Préparation des données ---
        $sortie = new Sortie();
        $sortie->setStartDateTime(new \DateTime('-1 day'));
        $user = new User();
        $sortie->addParticipant($user);

        $this->expectException(SortieException::class);
        $this->expectExceptionMessage('Impossible de se désister, la sortie a déjà commencé');
        $this->entityManagerMock->expects($this->never())
                                ->method('flush');

        // --- Exécution de la méthode à tester ---
        $this->sortieService->desinscrire($sortie, $user);
    }

    /**
     * @dataProvider provideInvalidStatesForDesinscrire
     */
    public function testDesinscrireSortieStateInvalid(State $state, string $expectedMessage): void
    {
        // --- Préparation des données ---
        $sortie = new Sortie();
        $sortie->setStartDateTime(new \DateTime('+1 day'));
        $sortie->setState($state);
        $user = new User();
        $sortie->addParticipant($user);

        $this->expectException(SortieException::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->entityManagerMock->expects($this->never())
                                ->method('flush');

        // --- Exécution de la méthode à tester ---
        $this->sortieService->desinscrire($sortie, $user);
    }

    public static function provideInvalidStatesForDesinscrire(): array
    {
        return [
            'Sortie en cours' => [State::IN_PROGRESS, 'Impossible de se désister : Activité en cours'],
            'Sortie passée' => [State::PASSED, 'Impossible de se désister : Passée'],
            'Sortie annulée' => [State::CANCELLED, 'Impossible de se désister : Annulée'],
        ];
    }

    /**
     * Teste l'annulation réussie d'une sortie par son organisateur.
     */
    public function testCancelSuccessOrganisateur() {
        $organizer = $this->createMock(User::class);
        $organizer->method('getId')->willReturn(1);
        $sortie = (new Sortie())
            ->setOrganisateur($organizer)
            ->setState(State::OPEN)
            ->setDescription('Description originale');
        $motif = 'Mauvais temps';

        $this->entityManagerMock->expects($this->once())
                                ->method('flush');

        // --- Exécution de la méthode à tester ---
        $this->sortieService->cancel($sortie, $organizer, $motif);

        // --- Vérifications ---
        $this->assertEquals(State::CANCELLED, $sortie->getState());
        $this->assertStringContainsString('=== SORTIE ANNULÉE ===', $sortie->getDescription());
        $this->assertStringContainsString('Motif : ' . $motif, $sortie->getDescription());
    }

    /**
     * Teste l'annulation réussie d'une sortie par un administrateur.
     */
    public function testCancelSuccessAdmin(): void
    {
        // --- Préparation des données ---
        $organizer = $this->createMock(User::class);
        $organizer->method('getId')->willReturn(1);
        $sortie = (new Sortie())
            ->setOrganisateur($organizer)
            ->setState(State::OPEN)
            ->setDescription('Description originale');
        $admin = $this->createMock(User::class);
        $admin->method('getId')->willReturn(2);
        $admin->method('getRoles')->willReturn(['ROLE_ADMIN']);
        $motif = 'Problème technique';

        $this->entityManagerMock->expects($this->once())
                                ->method('flush');

        // --- Exécution de la méthode à tester ---
        $this->sortieService->cancel($sortie, $admin, $motif);

        // --- Vérifications ---
        $this->assertEquals(State::CANCELLED, $sortie->getState());
        $this->assertStringContainsString('=== SORTIE ANNULÉE ===', $sortie->getDescription());
        $this->assertStringContainsString('Motif : ' . $motif, $sortie->getDescription());
    }

    /**
     * Teste l'annulation d'une sortie par un utilisateur non autorisé.
     */
    public function testCancelNotAuthorized(): void
    {
        // --- Préparation des données ---
        $organizer = $this->createMock(User::class);
        $organizer->method('getId')->willReturn(1);
        $unauthorizedUser = $this->createMock(User::class);
        $unauthorizedUser->method('getId')->willReturn(2);
        $sortie = (new Sortie())
            ->setOrganisateur($organizer)
            ->setState(State::OPEN);
        $motif = 'Test';

        $this->expectException(SortieException::class);
        $this->expectExceptionMessage('Vous n\'êtes pas autorisé à annuler cette sortie');
        $this->entityManagerMock->expects($this->never())
                                ->method('flush');

        // --- Exécution de la méthode à tester ---
        $this->sortieService->cancel($sortie, $unauthorizedUser, $motif);
    }

    /**
     * Teste l'annulation d'une sortie déjà passée.
     */
    public function testCancelSortieAlreadyPassed(): void
    {
        // --- Préparation des données ---
        $organizer = $this->createMock(User::class);
        $organizer->method('getId')->willReturn(1);
        $sortie = (new Sortie())
            ->setOrganisateur($organizer)
            ->setStartDateTime(new \DateTime('-1 day'))
            ->setState(State::PASSED);
        $motif = 'Test';

        $this->expectException(SortieException::class);
        $this->expectExceptionMessage('Cette sortie ne peut plus être annulée');
        $this->entityManagerMock->expects($this->never())
                                ->method('flush');

        // --- Exécution de la méthode à tester ---
        $this->sortieService->cancel($sortie, $organizer, $motif);
    }

    /**
     * Teste l'annulation d'une sortie déjà annulée.
     */
    public function testCancelSortieAlreadyCancelled(): void
    {
        // --- Préparation des données ---
        $organizer = $this->createMock(User::class);
        $organizer->method('getId')->willReturn(1);
        $sortie = (new Sortie())
            ->setOrganisateur($organizer)
            ->setState(State::CANCELLED);
        $motif = 'Test';

        $this->expectException(SortieException::class);
        $this->expectExceptionMessage('Cette sortie ne peut plus être annulée');
        $this->entityManagerMock->expects($this->never())
                                ->method('flush');

        // --- Exécution de la méthode à tester ---
        $this->sortieService->cancel($sortie, $organizer, $motif);
    }
}
