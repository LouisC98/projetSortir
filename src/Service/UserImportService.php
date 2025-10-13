<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\SiteRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserImportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
        private readonly SiteRepository $siteRepository,
        private readonly ValidatorInterface $validator
    ) {}

    /**
     * Importe des utilisateurs depuis un fichier CSV
     *
     * Lit un fichier CSV ligne par ligne, valide les données, vérifie les doublons
     * (email, pseudo) et crée les utilisateurs avec un mot de passe temporaire.
     *
     * @param File $file Le fichier CSV à importer
     *
     * @return array{successful: int, errors: array<string>}
     *               Tableau contenant le nombre d'utilisateurs créés et la liste des erreurs
     *
     * @throws RuntimeException|RandomException Si le fichier ne peut pas être ouvert
     */
    public function importUsers(File $file): array
    {
        $handle = fopen($file->getPathname(), 'r');
        if (!$handle) {
            throw new RuntimeException('Impossible d\'ouvrir le fichier CSV.');
        }

        // Lire l\'en-tête pour avoir des clés associatives
        $header = fgetcsv($handle);
        if (empty($header)) {
            fclose($handle);
            return ['successful' => 0, 'errors' => ['Le fichier CSV est vide ou n\'a pas d\'en-tête.']];
        }

        $successfulCount = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count($row) !== count($header)) {
                $errors[] = sprintf('Ligne %d: Le nombre de colonnes ne correspond pas à l\'en-tête.', $rowNumber);
                continue;
            }

            $record = array_combine($header, $row);

            // --- Validations ---
            if ($this->userRepository->findOneBy(['email' => $record['email']])) {
                $errors[] = sprintf('Ligne %d: L\'email "%s" est déjà utilisé.', $rowNumber, $record['email']);
                continue;
            }

            if ($this->userRepository->findOneBy(['pseudo' => $record['pseudo']])) {
                $errors[] = sprintf('Ligne %d: Le pseudo "%s" est déjà utilisé.', $rowNumber, $record['pseudo']);
                continue;
            }

            $site = $this->siteRepository->findOneBy(['name' => $record['site_nom']]);
            if (!$site) {
                $errors[] = sprintf('Ligne %d: Le site "%s" n\'existe pas.', $rowNumber, $record['site_nom']);
                continue;
            }

            // --- Création de l\'utilisateur ---
            $user = new User();
            $user->setEmail($record['email']);
            $user->setPseudo($record['pseudo']);
            $user->setLastName($record['nom']);
            $user->setFirstName($record['prenom']);
            $user->setPhone($record['telephone']);
            $user->setSite($site);
            $user->setRoles(['ROLE_USER']);
            $user->setActive(true);

            $tempPassword = bin2hex(random_bytes(8));
            $user->setPassword($this->passwordHasher->hashPassword($user, $tempPassword));

            // --- Validation de l\'entité ---
            $violations = $this->validator->validate($user);
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $errors[] = sprintf('Ligne %d (%s): %s', $rowNumber, $violation->getPropertyPath(), $violation->getMessage());
                }
                continue;
            }

            $this->entityManager->persist($user);
            $successfulCount++;
        }

        fclose($handle);

        if ($successfulCount > 0) {
            $this->entityManager->flush();
        }

        return [
            'successful' => $successfulCount,
            'errors' => $errors,
        ];
    }
}