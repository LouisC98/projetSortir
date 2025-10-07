<?php

namespace App\Tests\Controller;

use App\Enum\State;
use App\Repository\PlaceRepository;
use App\Repository\SortieRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SortieControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Initialise le client de test.
        $this->client = static::createClient();

        // Récupère les services nécessaires via le conteneur.
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->placeRepository = $container->get(PlaceRepository::class);
    }

    /**
     * Teste la création d'une sortie par un utilisateur authentifié.
     */
    public function testCreateSortie(): void
    {
        $testUser = $this->userRepository->findOneByEmail('user@test.com');
        $this->client->loginUser($testUser);
        $testPlace = $this->placeRepository->findOneBy([]);

        $this->client->request('GET', '/sortie/new');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Créer une sortie');

        $this->client->submitForm('Enregistrer', [
            'sortie_form' => [
                'name' => 'Ma Super Sortie de Test',
                'startDateTime' => '2026-01-15 20:00',
                'registrationDeadline' => '2026-01-10',
                'maxRegistration' => 10,
                'duration' => 120,
                'description' => 'Ceci est une description de test.',
                'place' => $testPlace->getId(),
            ]
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorTextContains('h1', 'Ma Super Sortie de Test');
        $this->assertSelectorTextContains('.sortie-description', 'Ceci est une description de test.');
    }

    /**
     * Vérifie qu'un utilisateur peut s'inscrire à une sortie.
     */
    public function testRegisterToSortie(): void
    {
        $testUser = $this->userRepository->findOneByEmail('user@test.com');
        $this->client->loginUser($testUser);

        $query = $this->entityManager->createQuery(
            'SELECT s FROM App\Entity\Sortie s WHERE s.state = :state AND s.startDateTime > :now AND SIZE(s.participants) < s.maxRegistration AND s.organisateur != :user'
        )->setParameters([
            'state' => \App\Enum\State::OPEN,
            'now' => new \DateTimeImmutable(),
            'user' => $testUser
        ])->setMaxResults(1);
        $sortieToJoin = $query->getOneOrNullResult();
        $this->assertNotNull($sortieToJoin, "Aucune sortie disponible pour l'inscription n'a été trouvée pour le test.");

        $this->client->request('GET', '/sortie/' . $sortieToJoin->getId());
        $this->assertResponseIsSuccessful();
        $this->client->submitForm("S'inscrire");

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorTextContains('h1', $sortieToJoin->getName());
        $this->assertSelectorTextContains('body', $testUser->getPseudo());
    }

    /**
     * Vérifie qu'un utilisateur peut se désister d'une sortie.
     */
    public function testUnregisterFromSortie(): void
    {
        $testUser = $this->userRepository->findOneByEmail('user@test.com');

        $query = $this->entityManager->createQuery(
            'SELECT s FROM App\Entity\Sortie s JOIN s.participants p WHERE p.id = :userId AND s.startDateTime > :now'
        )->setParameters([
            'userId' => $testUser->getId(),
            'now' => new \DateTimeImmutable(),
        ])->setMaxResults(1);
        $sortieToLeave = $query->getOneOrNullResult();
        $this->assertNotNull($sortieToLeave, "Aucune sortie à laquelle l'utilisateur est inscrit n'a été trouvée pour le test.");

        $this->client->loginUser($testUser);
        $this->client->request('GET', '/sortie/' . $sortieToLeave->getId());
        $this->assertResponseIsSuccessful("Impossible de charger la page de la sortie.");
        $this->assertSelectorTextContains('body', $testUser->getPseudo(), "Le pseudo de l'utilisateur n'apparaît pas dans la liste des participants avant le désistement.");
        $this->client->submitForm('Se désister');

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorTextNotContains('#participants-list', $testUser->getPseudo(), "Le pseudo de l'utilisateur est toujours présent après le désistement.");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager = null;
        $this->userRepository = null;
        $this->sortieRepository = null;
        $this->placeRepository = null;
        $this->client = null;
    }

    /**
     * Teste l'annulation d'une sortie par son organisateur.
     */
    public function testCancelSortie(): void
    {
        // Prépare le test.
        $testUser = $this->userRepository->findOneByEmail('user@test.com');

        // Cherche une sortie organisée par l'utilisateur, qui n'est ni passée ni annulée.
        $query = $this->entityManager->createQuery(
            'SELECT s FROM App\Entity\Sortie s WHERE s.organisateur = :user AND s.state IN (:cancellableStates) AND s.startDateTime > :now'
        )->setParameters([
            'user' => $testUser,
            'cancellableStates' => [State::CREATED, State::OPEN],
            'now' => new \DateTimeImmutable(),
        ])->setMaxResults(1);
        $sortieToCancel = $query->getOneOrNullResult();

        $this->assertNotNull($sortieToCancel, "Aucune sortie à annuler n'a été trouvée pour le test.");

        // Exécute le scénario d'annulation.
        $this->client->loginUser($testUser);
        $this->client->request('GET', '/sortie/' . $sortieToCancel->getId() . '/cancel');
        $this->assertResponseIsSuccessful("Impossible de charger la page d'annulation.");

        $motif = "Annulation pour cause de mauvais temps.";
        $this->client->submitForm('Enregistrer', [
            'cancel_sortie_form' => [
                'motif' => $motif,
            ]
        ]);

        // Vérifie le résultat.
        $this->assertResponseRedirects();
        $crawler = $this->client->followRedirect();

        // S'assure que l'état de la sortie est bien "Annulée".
        $this->assertSelectorTextContains('span.inline-flex', 'Annulée');

        // Vérifie que le motif d'annulation est affiché.
        $this->assertSelectorTextContains('.sortie-description', $motif);
    }
}
