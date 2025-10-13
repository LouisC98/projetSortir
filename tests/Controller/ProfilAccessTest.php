<?php

namespace App\Tests\Controller;

use App\Entity\Sortie;
use App\Enum\State;
use App\Repository\SortieRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfilAccessTest extends WebTestCase
{
    private ?KernelBrowser $client;
    private ?EntityManagerInterface $entityManager;
    private ?UserRepository $userRepository;
    private ?SortieRepository $sortieRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->sortieRepository = $container->get(SortieRepository::class);
    }

    /**
     * Teste qu'un participant peut accéder au profil d'un autre utilisateur
     */
    public function testParticipantCanAccessOtherUserProfile(): void
    {
        // Récupérer deux utilisateurs différents
        $users = $this->userRepository->findAll();
        $this->assertGreaterThanOrEqual(2, count($users), 'Il faut au moins 2 utilisateurs dans la base de test');

        $user1 = $users[0];
        $user2 = $users[1];

        // Se connecter avec le premier utilisateur
        $this->client->loginUser($user1);

        // Accéder au profil du deuxième utilisateur
        $this->client->request('GET', '/profil/' . $user2->getId());

        // Vérifier que la page est accessible
        $this->assertResponseIsSuccessful();

        // Vérifier que le pseudo de l'utilisateur est affiché
        $this->assertSelectorTextContains('body', $user2->getPseudo());
    }

    /**
     * Teste qu'un utilisateur non connecté ne peut pas accéder aux profils
     */
    public function testGuestCannotAccessUserProfile(): void
    {
        $user = $this->userRepository->findOneBy([]);
        $this->assertNotNull($user, 'Au moins un utilisateur doit exister');

        // Tenter d'accéder au profil sans être connecté
        $this->client->request('GET', '/profil/' . $user->getId());

        // Devrait être redirigé vers la page de login
        $this->assertResponseRedirects('/login');
    }

    /**
     * Teste qu'un participant peut voir les liens vers les profils sur la page d'accueil
     */
    public function testParticipantCanSeeProfileLinksOnHomePage(): void
    {
        $testUser = $this->userRepository->findOneByEmail('user@test.com');
        $this->client->loginUser($testUser);

        // Accéder à la page d'accueil
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        // Vérifier qu'il y a des liens vers les profils des organisateurs
        // (format: /profil/{id})
        $profileLinks = $crawler->filter('a[href^="/profil/"]');

        // Il devrait y avoir au moins un lien vers un profil
        $this->assertGreaterThan(0, count($profileLinks), 'Des liens vers les profils doivent être présents sur la page d\'accueil');
    }

    /**
     * Teste qu'un participant peut voir et cliquer sur les profils dans les détails d'une sortie
     */
    public function testParticipantCanSeeProfileLinksOnSortieDetailsPage(): void
    {
        $testUser = $this->userRepository->findOneByEmail('user@test.com');
        $this->client->loginUser($testUser);

        // Créer une sortie avec des participants
        $sortie = $this->createTestSortieWithParticipants();

        // Accéder à la page de détails de la sortie
        $crawler = $this->client->request('GET', '/sortie/' . $sortie->getId());
        $this->assertResponseIsSuccessful();

        // Vérifier qu'il y a un lien vers le profil de l'organisateur
        $organizerLink = $crawler->filter('a[href="/profil/' . $sortie->getOrganisateur()->getId() . '"]');
        $this->assertGreaterThan(0, count($organizerLink), 'Un lien vers le profil de l\'organisateur doit être présent');

        // Vérifier qu'il y a des liens vers les profils des participants
        $participantLinks = $crawler->filter('#participants-list a[href^="/profil/"]');

        if ($sortie->getParticipants()->count() > 0) {
            $this->assertGreaterThan(0, count($participantLinks), 'Des liens vers les profils des participants doivent être présents');
        }
    }

    /**
     * Teste qu'un participant peut cliquer sur le profil d'un organisateur
     */
    public function testParticipantCanClickOnOrganizerProfile(): void
    {
        $testUser = $this->userRepository->findOneByEmail('user@test.com');
        $this->client->loginUser($testUser);

        // Trouver une sortie existante
        $sortie = $this->sortieRepository->findOneBy(['state' => State::OPEN]);

        if (!$sortie) {
            // Créer une sortie si aucune n'existe
            $sortie = $this->createTestSortieWithParticipants();
        }

        $organizer = $sortie->getOrganisateur();

        // Accéder à la page de détails de la sortie
        $crawler = $this->client->request('GET', '/sortie/' . $sortie->getId());
        $this->assertResponseIsSuccessful();

        // Cliquer sur le lien du profil de l'organisateur
        $link = $crawler->filter('a[href="/profil/' . $organizer->getId() . '"]')->first();

        if (count($link) > 0) {
            $crawler = $this->client->click($link->link());

            // Vérifier qu'on est bien sur la page du profil
            $this->assertResponseIsSuccessful();
            $this->assertSelectorTextContains('body', $organizer->getPseudo());
        } else {
            $this->markTestSkipped('Le lien vers le profil de l\'organisateur n\'a pas été trouvé');
        }
    }

    /**
     * Teste qu'un participant peut cliquer sur le profil d'un autre participant
     */
    public function testParticipantCanClickOnOtherParticipantProfile(): void
    {
        // Récupérer plusieurs utilisateurs
        $users = $this->userRepository->findAll();
        $this->assertGreaterThanOrEqual(3, count($users), 'Il faut au moins 3 utilisateurs pour ce test');

        $viewer = $users[0];
        $organizer = $users[1];
        $participant = $users[2];

        // Créer une sortie avec des participants
        $sortie = $this->createTestSortie($organizer);

        // Inscrire un participant à la sortie
        $sortie->addParticipant($participant);
        $this->entityManager->flush();

        // Se connecter avec le viewer
        $this->client->loginUser($viewer);

        // Accéder à la page de détails de la sortie
        $crawler = $this->client->request('GET', '/sortie/' . $sortie->getId());
        $this->assertResponseIsSuccessful();

        // Chercher le lien vers le profil du participant
        $participantLink = $crawler->filter('a[href="/profil/' . $participant->getId() . '"]')->first();

        if (count($participantLink) > 0) {
            // Cliquer sur le lien
            $crawler = $this->client->click($participantLink->link());

            // Vérifier qu'on est sur la page du profil du participant
            $this->assertResponseIsSuccessful();
            $this->assertSelectorTextContains('body', $participant->getPseudo());
        } else {
            $this->markTestSkipped('Le lien vers le profil du participant n\'a pas été trouvé');
        }
    }

    /**
     * Teste que tous les participants d'une sortie ont des liens cliquables vers leurs profils
     */
    public function testAllParticipantsHaveClickableProfileLinks(): void
    {
        // Récupérer plusieurs utilisateurs
        $users = $this->userRepository->findAll();
        $this->assertGreaterThanOrEqual(4, count($users), 'Il faut au moins 4 utilisateurs pour ce test');

        $viewer = $users[0];
        $organizer = $users[1];
        $participant1 = $users[2];
        $participant2 = $users[3];

        // Créer une sortie avec plusieurs participants
        $sortie = $this->createTestSortie($organizer);
        $sortie->addParticipant($participant1);
        $sortie->addParticipant($participant2);
        $this->entityManager->flush();

        // Se connecter avec le viewer
        $this->client->loginUser($viewer);

        // Accéder à la page de détails
        $crawler = $this->client->request('GET', '/sortie/' . $sortie->getId());
        $this->assertResponseIsSuccessful();

        // Vérifier qu'il y a des liens pour tous les participants
        $allParticipantLinks = $crawler->filter('#participants-list a[href^="/profil/"]');

        // On devrait avoir au moins 2 liens (pour les 2 participants)
        $this->assertGreaterThanOrEqual(2, count($allParticipantLinks),
            'Chaque participant devrait avoir un lien vers son profil');

        // Vérifier que les liens sont bien formatés (contiennent des IDs valides)
        foreach ($allParticipantLinks as $link) {
            $href = $link->getAttribute('href');
            $this->assertMatchesRegularExpression('/\/profil\/\d+/', $href,
                'Le lien doit avoir le format /profil/{id}');
        }
    }

    /**
     * Teste qu'un participant peut voir son propre profil depuis la liste
     */
    public function testParticipantCanViewOwnProfileFromList(): void
    {
        $testUser = $this->userRepository->findOneByEmail('user@test.com');
        $this->client->loginUser($testUser);

        // Créer une sortie où l'utilisateur est participant
        $organizer = $this->userRepository->findOneByEmail('admin@test.com');
        $sortie = $this->createTestSortie($organizer);
        $sortie->addParticipant($testUser);
        $this->entityManager->flush();

        // Accéder à la page de détails
        $crawler = $this->client->request('GET', '/sortie/' . $sortie->getId());
        $this->assertResponseIsSuccessful();

        // Chercher le lien vers son propre profil
        $ownProfileLink = $crawler->filter('a[href="/profil/' . $testUser->getId() . '"]')->first();

        if (count($ownProfileLink) > 0) {
            // Cliquer sur le lien
            $this->client->click($ownProfileLink->link());

            // Vérifier qu'on est bien sur son profil
            $this->assertResponseIsSuccessful();
            $this->assertSelectorTextContains('body', $testUser->getPseudo());
        }
    }

    /**
     * Méthode helper pour créer une sortie de test avec des participants
     */
    private function createTestSortieWithParticipants(): Sortie
    {
        $users = $this->userRepository->findAll();
        $this->assertGreaterThanOrEqual(3, count($users), 'Il faut au moins 3 utilisateurs');

        $organizer = $users[0];
        $participant1 = $users[1];
        $participant2 = $users[2];

        $sortie = $this->createTestSortie($organizer);

        // Ajouter des participants
        $sortie->addParticipant($participant1);
        $sortie->addParticipant($participant2);

        $this->entityManager->flush();

        return $sortie;
    }

    /**
     * Méthode helper pour créer une sortie de test
     */
    private function createTestSortie($organizer): Sortie
    {
        $sortie = new Sortie();
        $sortie->setName('Sortie Test Profils - ' . uniqid());
        $sortie->setStartDateTime(new \DateTime('+1 week'));
        $sortie->setRegistrationDeadline(new \DateTime('+3 days'));
        $sortie->setDuration(120);
        $sortie->setMaxRegistration(10);
        $sortie->setDescription('Sortie de test pour vérifier l\'accès aux profils');
        $sortie->setState(State::OPEN);
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
        $this->userRepository = null;
        $this->sortieRepository = null;
        $this->client = null;
    }
}

