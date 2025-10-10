<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResetPasswordControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);

        // Nettoyer les tokens de reset existants
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM reset_password_request');
    }

    public function testRequestPageDisplaysCorrectly(): void
    {
        $this->client->request('GET', '/reset-password');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mot de passe oublié');
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="reset_password_request_form[email]"]');
    }

    public function testRequestWithValidEmailGeneratesToken(): void
    {
        $this->client->request('GET', '/reset-password');

        $this->client->submitForm('Envoyer', [
            'reset_password_request_form[email]' => 'user@test.com',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a[href*="/reset-password/reset/"]');
        // Texte exact du template
        $this->assertSelectorTextContains('p', 'Le processus de réinitialisation a été initié');
    }

    public function testRequestWithNonExistentEmailShowsGenericMessage(): void
    {
        $this->client->request('GET', '/reset-password');

        $this->client->submitForm('Envoyer', [
            'reset_password_request_form[email]' => 'nonexistent@test.com',
        ]);

        $this->assertResponseIsSuccessful();
        // Message générique affiché dans les deux cas
        $this->assertSelectorTextContains('p', 'Le processus de réinitialisation a été initié');
        $this->assertSelectorNotExists('a[href*="/reset-password/reset/"]');
    }

    public function testRequestWithEmptyEmailShowsValidationError(): void
    {
        $this->client->request('GET', '/reset-password');

        $this->client->submitForm('Envoyer', [
            'reset_password_request_form[email]' => '',
        ]);

        // Symfony retourne 422 pour une erreur de validation
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('.text-red-600');
    }

    public function testResetWithValidTokenDisplaysForm(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user@test.com']);
        $resetPasswordHelper = static::getContainer()->get(ResetPasswordHelperInterface::class);
        $resetToken = $resetPasswordHelper->generateResetToken($user);

        $this->client->request('GET', '/reset-password/reset/' . $resetToken->getToken());
        $this->assertResponseRedirects('/reset-password/reset');

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Créez votre nouveau mot de passe');
        $this->assertSelectorExists('input[name="change_password_form[plainPassword][first]"]');
        $this->assertSelectorExists('input[name="change_password_form[plainPassword][second]"]');
    }

    public function testResetWithInvalidTokenRedirectsWithError(): void
    {
        $this->client->request('GET', '/reset-password/reset/invalid-token-123');
        $this->assertResponseRedirects('/reset-password/reset');

        $this->client->followRedirect();
        $this->assertResponseRedirects('/reset-password');

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mot de passe oublié');

        $this->assertSelectorExists('.bg-red-100');
        $this->assertSelectorTextContains('.text-red-700', 'There was a problem validating your password reset request');
    }

    public function testResetPasswordSuccessfully(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user@test.com']);
        $oldPasswordHash = $user->getPassword();

        $resetPasswordHelper = static::getContainer()->get(ResetPasswordHelperInterface::class);
        $resetToken = $resetPasswordHelper->generateResetToken($user);

        // Visite la page avec le token
        $this->client->request('GET', '/reset-password/reset/' . $resetToken->getToken());
        $this->client->followRedirect();

        // Soumet le formulaire de nouveau mot de passe
        $this->client->submitForm('Réinitialiser le mot de passe', [
            'change_password_form[plainPassword][first]' => 'NewSecureP@ssw0rd!',
            'change_password_form[plainPassword][second]' => 'NewSecureP@ssw0rd!',
        ]);

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();

        // Re-fetch l'utilisateur depuis la DB pour avoir l'entité managée
        $updatedUser = $this->userRepository->findOneBy(['email' => 'user@test.com']);
        $this->assertNotEquals($oldPasswordHash, $updatedUser->getPassword());

        // Vérifie que le token a été supprimé
        $connection = $this->entityManager->getConnection();
        $result = $connection->executeQuery('SELECT COUNT(*) FROM reset_password_request')->fetchOne();
        $this->assertEquals(0, $result);
    }

    public function testResetWithMismatchedPasswordsShowsError(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user@test.com']);
        $resetPasswordHelper = static::getContainer()->get(ResetPasswordHelperInterface::class);
        $resetToken = $resetPasswordHelper->generateResetToken($user);

        $this->client->request('GET', '/reset-password/reset/' . $resetToken->getToken());
        $this->client->followRedirect();

        $this->client->submitForm('Réinitialiser le mot de passe', [
            'change_password_form[plainPassword][first]' => 'NewSecureP@ssw0rd!',
            'change_password_form[plainPassword][second]' => 'DifferentP@ssw0rd!',
        ]);

        // Symfony retourne 422 pour une erreur de validation
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('.text-red-600');
    }

    public function testResetWithTooShortPasswordShowsError(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user@test.com']);
        $resetPasswordHelper = static::getContainer()->get(ResetPasswordHelperInterface::class);
        $resetToken = $resetPasswordHelper->generateResetToken($user);

        $this->client->request('GET', '/reset-password/reset/' . $resetToken->getToken());
        $this->client->followRedirect();

        $this->client->submitForm('Réinitialiser le mot de passe', [
            'change_password_form[plainPassword][first]' => '12345',
            'change_password_form[plainPassword][second]' => '12345',
        ]);

        // Symfony retourne 422 pour une erreur de validation
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('.text-red-600');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}