<?php

namespace App\Tests\Controller;

use App\Entity\Site;
use App\Repository\SiteRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SiteControllerTest extends WebTestCase
{
    private function loginAsAdmin($client): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneByEmail('admin@test.com');
        $client->loginUser($adminUser);
    }

    /**
     * Nettoyer les sites de test créés
     */
    private function cleanupTestSites(EntityManagerInterface $entityManager): void
    {
        $testSiteNames = [
            'Un Site de Test',
            'Site à Modifier',
            'Site qui a été Modifié',
            'Site à Supprimer'
        ];

        $entityManager->createQuery(
            'DELETE FROM App\Entity\Site s WHERE s.name IN (:names)'
        )
            ->setParameter('names', $testSiteNames)
            ->execute();
    }

    public function testIndexPageIsRendered(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);
        $client->request('GET', '/admin/site/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste des sites');
    }

    public function testCreateNewSite(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Nettoyer avant le test
        $this->cleanupTestSites($entityManager);

        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/admin/site/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'site_form[name]' => 'Un Site de Test',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/admin/site/');
        $crawler = $client->followRedirect();

        $this->assertSelectorExists('.alert-success');
        $this->assertSelectorTextContains('body', 'Un Site de Test');

        // Nettoyer après le test
        $this->cleanupTestSites($entityManager);
    }

    public function testEditSite(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Nettoyer avant le test
        $this->cleanupTestSites($entityManager);

        $this->loginAsAdmin($client);

        $site = new Site();
        $site->setName('Site à Modifier');
        $entityManager->persist($site);
        $entityManager->flush();

        $crawler = $client->request('GET', '/admin/site/' . $site->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'site_form[name]' => 'Site qui a été Modifié',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/admin/site/');
        $crawler = $client->followRedirect();

        $this->assertSelectorExists('.alert-success');
        $this->assertSelectorTextContains('body', 'Site qui a été Modifié');
        $this->assertSelectorTextNotContains('body', 'Site à Modifier');

        // Nettoyer après le test
        $this->cleanupTestSites($entityManager);
    }

    public function testDeleteSite(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $siteRepository = static::getContainer()->get(SiteRepository::class);

        // Nettoyer avant le test
        $this->cleanupTestSites($entityManager);

        $this->loginAsAdmin($client);

        $site = new Site();
        $site->setName('Site à Supprimer');
        $entityManager->persist($site);
        $entityManager->flush();

        $siteId = $site->getId();

        $crawler = $client->request('GET', '/admin/site/');
        $this->assertSelectorTextContains('body', 'Site à Supprimer');

        $form = $crawler->filter('form[action="/admin/site/' . $siteId . '/delete"]')->form();
        $client->submit($form);

        $this->assertResponseRedirects('/admin/site/');
        $crawler = $client->followRedirect();

        $this->assertSelectorExists('.alert-success');
        $this->assertSelectorTextNotContains('body', 'Site à Supprimer');

        // Vérifier en base de données
        $entityManager->clear();
        $deletedSite = $siteRepository->find($siteId);
        $this->assertNull($deletedSite, 'Le site devrait être supprimé de la base de données');

        // Nettoyer après le test (au cas où)
        $this->cleanupTestSites($entityManager);
    }
}