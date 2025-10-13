<?php

namespace App\Tests\Controller;

use App\Entity\Sortie;
use App\Entity\User;
use App\Enum\State;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SortieInscriptionTest extends WebTestCase
{
    private ?KernelBrowser $client;
    private ?EntityManagerInterface $entityManager;
    private ?UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
    }

    /**
     * Teste qu'un utilisateur peut s'inscrire à une sortie AVANT la date limite d'inscription.
     */
    public function testUserCanRegisterBeforeDeadline(): void
    {
        $testUser = $this->userRepository->findOneByEmail('user@test.com');
        $this->client->loginUser($testUser);

        // Créer une sortie avec une date limite d'inscription dans le futur
        $sortie = $this->createTestSortie(
            registrationDeadline: new \DateTime('+2 days'),
            startDateTime: new \DateTime('+3 days')
        );

        // Vérifier que l'utilisateur n'est pas encore inscrit
        $this->assertFalse($sortie->getParticipants()->contains($testUser));

        // Accéder à la page de la sortie
        $crawler = $this->client->request('GET', '/sortie/' . $sortie->getId());
        $this->assertResponseIsSuccessful();

        // Soumettre le formulaire d'inscription
        $form = $crawler->selectButton('S\'inscrire')->form();
        $this->client->submit($form);

        // Vérifier la redirection et le message de succès
        $this->assertResponseRedirects('/sortie/' . $sortie->getId());
        $this->client->followRedirect();
        $this->assertSelectorExists('.bg-green-100');

        // Vérifier en base de données que l'utilisateur est bien inscrit
        $sortieId = $sortie->getId();
        $userId = $testUser->getId();
        $this->entityManager->clear();
        $sortie = $this->entityManager->getRepository(Sortie::class)->find($sortieId);
        $testUser = $this->entityManager->getRepository(User::class)->find($userId);
        $this->assertTrue($sortie->getParticipants()->contains($testUser));
    }

    /**
     * Teste qu'un utilisateur NE PEUT PAS s'inscrire à une sortie APRÈS la date limite d'inscription.
     */
    public function testUserCannotRegisterAfterDeadline(): void
    {
        $testUser = $this->userRepository->findOneByEmail('user@test.com');
        $this->client->loginUser($testUser);

        // Créer une sortie avec une date limite d'inscription DÉPASSÉE
        $sortie = $this->createTestSortie(
            registrationDeadline: new \DateTime('-1 day'),
            startDateTime: new \DateTime('+1 day')
        );

        // Vérifier que l'utilisateur n'est pas inscrit
        $this->assertFalse($sortie->getParticipants()->contains($testUser));

        // Accéder à la page de la sortie pour initialiser la session
        $crawler = $this->client->request('GET', '/sortie/' . $sortie->getId());
        $this->assertResponseIsSuccessful();

        // Vérifier que le bouton d'inscription n'est PAS affiché
        $this->assertSelectorNotExists('button:contains("S\'inscrire")');

        // Tenter de soumettre quand même une requête POST d'inscription en extrayant le token CSRF
        $csrfToken = $crawler->filter('input[name="_csrf_token"]')->count() > 0
            ? $crawler->filter('input[name="_csrf_token"]')->attr('value')
            : 'test-token';

        $this->client->request('POST', '/sortie/' . $sortie->getId() . '/inscription', [
            '_token' => $csrfToken,
        ]);

        // Vérifier la redirection et le message d'erreur
        $this->assertResponseRedirects('/sortie/' . $sortie->getId());
        $crawler = $this->client->followRedirect();

        // Vérifier qu'il y a un message d'erreur (classe bg-red-100)
        $this->assertSelectorExists('.bg-red-100');

        // Vérifier en base de données que l'utilisateur N'EST PAS inscrit
        $sortieId = $sortie->getId();
        $userId = $testUser->getId();
        $this->entityManager->clear();
        $sortie = $this->entityManager->getRepository(Sortie::class)->find($sortieId);
        $testUser = $this->entityManager->getRepository(User::class)->find($userId);
        $this->assertFalse($sortie->getParticipants()->contains($testUser));
    }


    /**
     * Teste que plusieurs utilisateurs ne peuvent pas s'inscrire après la date limite.
     */
    public function testMultipleUsersCannotRegisterAfterDeadline(): void
    {
        // Récupérer plusieurs utilisateurs
        $users = $this->userRepository->findAll();
        $this->assertGreaterThanOrEqual(2, count($users), 'Il faut au moins 2 utilisateurs dans la base de test');

        $user1 = $users[0];
        $user2 = $users[1];

        // Créer une sortie avec une date limite DÉPASSÉE
        $sortie = $this->createTestSortie(
            registrationDeadline: new \DateTime('-2 days'),
            startDateTime: new \DateTime('+1 day')
        );

        // Tester avec le premier utilisateur
        $this->client->loginUser($user1);

        // Accéder à la page pour initialiser la session
        $crawler = $this->client->request('GET', '/sortie/' . $sortie->getId());
        $this->assertResponseIsSuccessful();

        // Extraire le token CSRF si disponible
        $csrfToken1 = $crawler->filter('input[name="_csrf_token"]')->count() > 0
            ? $crawler->filter('input[name="_csrf_token"]')->attr('value')
            : 'test-token';

        $this->client->request('POST', '/sortie/' . $sortie->getId() . '/inscription', [
            '_token' => $csrfToken1,
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorExists('.bg-red-100');

        // Tester avec le deuxième utilisateur
        $this->client->loginUser($user2);

        // Accéder à la page pour initialiser la session
        $crawler = $this->client->request('GET', '/sortie/' . $sortie->getId());
        $this->assertResponseIsSuccessful();

        // Extraire le token CSRF si disponible
        $csrfToken2 = $crawler->filter('input[name="_csrf_token"]')->count() > 0
            ? $crawler->filter('input[name="_csrf_token"]')->attr('value')
            : 'test-token';

        $this->client->request('POST', '/sortie/' . $sortie->getId() . '/inscription', [
            '_token' => $csrfToken2,
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorExists('.bg-red-100');

        // Vérifier qu'AUCUN des deux utilisateurs n'est inscrit
        $sortieId = $sortie->getId();
        $user1Id = $user1->getId();
        $user2Id = $user2->getId();
        $this->entityManager->clear();
        $sortie = $this->entityManager->getRepository(Sortie::class)->find($sortieId);
        $user1 = $this->entityManager->getRepository(User::class)->find($user1Id);
        $user2 = $this->entityManager->getRepository(User::class)->find($user2Id);
        $this->assertFalse($sortie->getParticipants()->contains($user1));
        $this->assertFalse($sortie->getParticipants()->contains($user2));
        $this->assertEquals(0, $sortie->getParticipants()->count());
    }

    /**
     * Méthode helper pour créer une sortie de test
     */
    private function createTestSortie(
        \DateTime $registrationDeadline,
        \DateTime $startDateTime
    ): Sortie {
        $organizer = $this->userRepository->findOneByEmail('admin@test.com');

        $sortie = new Sortie();
        $sortie->setName('Sortie Test - ' . uniqid());
        $sortie->setStartDateTime($startDateTime);
        $sortie->setRegistrationDeadline($registrationDeadline);
        $sortie->setDuration(120);
        $sortie->setMaxRegistration(10);
        $sortie->setDescription('Sortie de test pour vérifier la date limite d\'inscription');
        $sortie->setState(State::OPEN);
        $sortie->setOrganisateur($organizer);
        $sortie->setSite($organizer->getSite());

        // Trouver un lieu existant pour la sortie
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
        $this->userRepository = null;
        $this->client = null;
    }
}
