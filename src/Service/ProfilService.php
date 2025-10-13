<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfilService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly SluggerInterface $slugger
    ) {
    }

    /**
     * Valide le changement de mot de passe
     *
     * Vérifie que l'ancien mot de passe est correct et que les nouveaux
     * mots de passe correspondent.
     *
     * @param User $user L'utilisateur concerné
     * @param string|null $oldPassword L'ancien mot de passe
     * @param string|null $newPassword Le nouveau mot de passe
     * @param string|null $confirmPassword La confirmation du nouveau mot de passe
     *
     * @return array<string> Tableau des erreurs de validation (vide si aucune erreur)
     */
    public function validatePasswordChange(User $user, ?string $oldPassword, ?string $newPassword, ?string $confirmPassword): array
    {
        $errors = [];

        if (!$oldPassword && !$newPassword && !$confirmPassword) {
            return $errors;
        }

        if (!$this->passwordHasher->isPasswordValid($user, $oldPassword)) {
            $errors[] = 'L\'ancien mot de passe est incorrect.';
            return $errors;
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Les nouveaux mots de passe ne correspondent pas.';
            return $errors;
        }

        return $errors;
    }

    /**
     * Met à jour le mot de passe de l'utilisateur
     *
     * Hash le nouveau mot de passe et l'associe à l'utilisateur.
     *
     * @param User $user L'utilisateur concerné
     * @param string $newPassword Le nouveau mot de passe en clair
     *
     * @return void
     */
    public function updatePassword(User $user, string $newPassword): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
    }

    /**
     * Sauvegarde les modifications du profil
     *
     * Persiste les changements de l'utilisateur en base de données.
     *
     * @param User $user L'utilisateur à sauvegarder
     *
     * @return void
     */
    public function saveProfil(User $user): void
    {
        $this->em->flush();
    }

    /**
     * Traite la modification du profil avec gestion du mot de passe et de la photo
     *
     * Gère l'upload de la photo de profil, la validation et la mise à jour du mot de passe,
     * puis sauvegarde les modifications.
     *
     * @param User $user L'utilisateur concerné
     * @param FormInterface $form Le formulaire de modification du profil
     *
     * @return array<string> Tableau des erreurs rencontrées (vide si succès)
     */
    public function processProfilUpdate(User $user, FormInterface $form): array
    {
        $errors = [];

        /**
         * Gestion de la photo de profil
         */
        if ($form->has('photoFile')) {
            $photoFile = $form->get('photoFile')->getData();

            if ($photoFile) {
                $safeFilename = $this->slugger->slug(pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                $uploadDir = __DIR__ . '/../../public/uploads/photos/';

                try {
                    $photoFile->move($uploadDir, $newFilename);

                    if ($user->getPhotoFilename() && file_exists($uploadDir . $user->getPhotoFilename())) {
                        @unlink($uploadDir . $user->getPhotoFilename());
                    }

                    $user->setPhotoFilename($newFilename);
                } catch (FileException $e) {
                    $errors[] = "Erreur lors de l’upload de la photo : " . $e->getMessage();
                }
            }
        }

        /**
         * Gestion du mot de passe
         */
        $oldPassword = $form->get('oldPassword')->getData();
        $newPassword = $form->get('newPassword')->getData();
        $confirmPassword = $form->get('confirmPassword')->getData();

        if ($oldPassword || $newPassword || $confirmPassword) {
            $errors = $this->validatePasswordChange($user, $oldPassword, $newPassword, $confirmPassword);

            if (empty($errors)) {
                $this->updatePassword($user, $newPassword);
            } else {
                return $errors;
            }
        }

        /**
         * Sauvegarde finale
         */
        $this->saveProfil($user);

        return $errors;
    }
}
