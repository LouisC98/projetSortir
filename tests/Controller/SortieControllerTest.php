<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SortieControllerTest extends WebTestCase
{
    /**
     * Teste le scénario complet de la création d'une sortie par un utilisateur connecté.
     */
    public function testCreateSortie(): void
    {
        // --- Etape 1 : Création du client et authentification ---

        // Crée un client HTTP pour simuler un navigateur
        $client = static::createClient();

        // Récupère le conteneur de services pour accéder aux Repositories
        $container = static::getContainer();

        // Récupère le UserRepository
        $userRepository = $container->get(UserRepository::class);

        // Récupère un utilisateur de test depuis la base de données (assurez-vous qu'il existe !)
        // Remplacez 'user@test.com' par un email existant dans votre base de données de test
        $testUser = $userRepository->findOneByEmail('user@test.com');

        // Simule la connexion de cet utilisateur
        $client->loginUser($testUser);

        // Récupère une entité "Place" pour l'associer à la sortie
        $placeRepository = $container->get(\App\Repository\PlaceRepository::class);
        $testPlace = $placeRepository->findOneBy([]); // Prend le premier lieu trouvé

        // --- Etape 2 : Accès à la page de création ---

        // Le client (connecté) demande la page de création de sortie
        $crawler = $client->request('GET', '/sortie/new');

        // On vérifie que la page se charge correctement (réponse HTTP 200)
        $this->assertResponseIsSuccessful();

        // On vérifie que le titre "Créer une sortie" est bien présent
        $this->assertSelectorTextContains('h1', 'Créer une sortie');


        // --- Etape 3 : Soumission du formulaire ---

        // On simule le remplissage et la soumission du formulaire
        $client->submitForm('Enregistrer', [
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


        // --- Etape 4 : Vérification du résultat ---

        // On vérifie que la soumission du formulaire a provoqué une redirection
        $this->assertResponseRedirects();

        // Le client suit automatiquement la redirection
        $crawler = $client->followRedirect();

        // Sur la nouvelle page (la page de la sortie créée), on vérifie que le nom est bien affiché
        $this->assertSelectorTextContains('h1', 'Ma Super Sortie de Test');
        $this->assertSelectorTextContains('.sortie-description', 'Ceci est une description de test.');
    }
}
