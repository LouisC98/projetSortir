<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\SiteRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistrationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
        private readonly SiteRepository $siteRepository
    ) {
    }

    /**
     * Valide les données d'inscription et retourne les erreurs éventuelles
     *
     * Vérifie que tous les champs requis sont présents, que l'email et le pseudo
     * ne sont pas déjà utilisés, que les mots de passe correspondent et que le site existe.
     *
     * @param array $data Les données d'inscription à valider
     *
     * @return array<string> Tableau des erreurs de validation (vide si aucune erreur)
     */
    public function validateRegistrationData(array $data): array
    {
        $errors = [];

        if (empty($data['email']) || empty($data['password']) || empty($data['pseudo']) || empty($data['siteId'])) {
            $errors[] = 'Champs requis manquants';
            return $errors;
        }

        if ($this->userRepository->findOneBy(['email' => $data['email']])) {
            $errors[] = 'Cet email est déjà utilisé';
        }

        if ($this->userRepository->findOneBy(['pseudo' => $data['pseudo']])) {
            $errors[] = 'Ce pseudo est déjà pris';
        }

        if ($data['password'] !== $data['passwordConfirm']) {
            $errors[] = 'Les mots de passe ne correspondent pas';
        }

        $site = $this->siteRepository->find($data['siteId']);
        if (!$site) {
            $errors[] = 'Site non trouvé';
        }

        return $errors;
    }

    /**
     * Crée un nouvel utilisateur avec les données fournies
     *
     * Instancie un nouvel utilisateur, hash le mot de passe et persiste en base de données.
     *
     * @param array $data Les données de l'utilisateur à créer
     *
     * @return User L'utilisateur créé
     *
     * @throws \Exception Si le site spécifié n'existe pas
     */
    public function createUser(array $data): User
    {
        $site = $this->siteRepository->find($data['siteId']);
        if (!$site) {
            throw new \Exception('Site non trouvé');
        }

        $user = new User();
        $user->setPseudo($data['pseudo']);
        $user->setEmail($data['email']);
        $user->setFirstName($data['firstName'] ?? null);
        $user->setLastName($data['lastName'] ?? null);
        $user->setPhone($data['phone'] ?? null);
        $user->setSite($site);
        $user->setActive(true);

        if (!empty($data['isAdmin'])) {
            $user->setRoles(['ROLE_ADMIN']);
        } else {
            $user->setRoles(['ROLE_USER']);
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Extrait les données de la requête
     *
     * Normalise les données de la requête HTTP pour les rendre exploitables
     * par les autres méthodes du service.
     *
     * @param array $requestData Les données brutes de la requête
     *
     * @return array Les données normalisées de l'inscription
     */
    public function extractRegistrationData(array $requestData): array
    {
        return [
            'pseudo' => $requestData['pseudo'] ?? null,
            'email' => $requestData['email'] ?? null,
            'firstName' => $requestData['firstName'] ?? null,
            'lastName' => $requestData['lastName'] ?? null,
            'phone' => $requestData['phone'] ?? null,
            'siteId' => $requestData['site'] ?? null,
            'password' => $requestData['password'] ?? null,
            'passwordConfirm' => $requestData['password_confirm'] ?? null,
            'isAdmin' => $requestData['is_admin'] ?? false,
        ];
    }
}
