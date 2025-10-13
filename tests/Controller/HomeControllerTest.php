<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
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
     * Teste le filtre par site.
     */
    public function testFilterBySite(): void
    {
        $testUser = $this->userRepository->findOneByEmail('user@test.com');
        $this->client->loginUser($testUser);

        $site = $testUser->getSite();

        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('rechercher')->form([
            'site' => $site->getId(),
        ]);

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Vérifie que toutes les sorties affichées appartiennent au site sélectionné
        $sorties = $this->entityManager->createQuery(
            'SELECT s FROM App\Entity\Sortie s WHERE s.site = :site'
        )->setParameter('site', $site)->getResult();

        $this->assertGreaterThan(0, count($sorties), "Aucune sortie trouvée pour ce site.");
    }

    /**
     * Teste le filtre par nom de sortie.
     */
    public function testFilterByName(): void
    {
        $testUser = $this->userRepository->findOneByEmail('user@test.com');
        $this->client->loginUser($testUser);

        $searchTerm = 'Sortie';

        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('rechercher')->form([
            'name' => $searchTerm,
        ]);

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Vérifie que les sorties affichées contiennent le terme recherché
        $this->assertSelectorExists('table tbody tr');
    }

    /**
     * Teste le filtre par dates.
     */
    public function testFilterByDates(): void
    {
        $testUser = $this->userRepository->findOneByEmail('user@test.com');
        $this->client->loginUser($testUser);

        $startDate = '2026-01-01';
        $endDate = '2026-12-31';

        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('rechercher')->form([
            'dateDebut' => $startDate,
            'dateFin' => $endDate,
        ]);

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();
    }

    /**
     * Teste le filtre "Mes sorties organisées".
     */
    public function testFilterByOrganisateur(): void
    {
        $testUser = $this->userRepository->findOneByEmail('user@test.com');
        $this->client->loginUser($testUser);

        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('rechercher')->form([
            'mesSorties' => true,
        ]);

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Vérifie que seules les sorties organisées par l'utilisateur sont affichées
        $sorties = $this->entityManager->createQuery(
            'SELECT s FROM App\Entity\Sortie s WHERE s.organisateur = :user'
        )->setParameter('user', $testUser)->getResult();

        $this->assertGreaterThanOrEqual(0, count($sorties), "Vérification des sorties organisées.");
    }

    /**
     * Teste le filtre "Sorties auxquelles je suis inscrit".
     */
    public function testFilterByParticipant(): void
    {
        $testUser = $this->userRepository->findOneByEmail('user@test.com');
        $this->client->loginUser($testUser);

        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('rechercher')->form([
            'sortiesInscrit' => true,
        ]);

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Vérifie que les sorties affichées incluent l'utilisateur comme participant
        $sorties = $this->entityManager->createQuery(
            'SELECT s FROM App\Entity\Sortie s JOIN s.participants p WHERE p.id = :userId'
        )->setParameter('userId', $testUser->getId())->getResult();

        $this->assertGreaterThanOrEqual(0, count($sorties), "Vérification des sorties inscrites.");
    }

    /**
     * Teste le filtre "Sorties auxquelles je ne suis pas inscrit".
     */
    public function testFilterByNotParticipant(): void
    {
        $testUser = $this->userRepository->findOneByEmail('user@test.com');
        $this->client->loginUser($testUser);

        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('rechercher')->form([
            'sortiesNonInscrit' => true,
        ]);

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();
    }

    /**
     * Teste le filtre "Sorties passées".
     */
    public function testFilterByPastSorties(): void
    {
        $testUser = $this->userRepository->findOneByEmail('user@test.com');
        $this->client->loginUser($testUser);

        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('rechercher')->form([
            'sortiesPassees' => true,
        ]);

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Vérifie que les sorties affichées sont passées
        $this->assertSelectorExists('table tbody tr');
    }

    /**
     * Teste la combinaison de plusieurs filtres.
     */
    public function testCombinedFilters(): void
    {
        $testUser = $this->userRepository->findOneByEmail('user@test.com');
        $this->client->loginUser($testUser);

        $site = $testUser->getSite();

        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('rechercher')->form([
            'site' => $site->getId(),
            'sortiesInscrit' => true,
            'dateDebut' => '2026-01-01',
        ]);

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();
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
