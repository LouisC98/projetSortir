<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfilService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * Valide le changement de mot de passe
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
     */
    public function updatePassword(User $user, string $newPassword): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
    }

    /**
     * Sauvegarde les modifications du profil
     */
    public function saveProfil(User $user): void
    {
        $this->em->flush();
    }

    /**
     * Traite la modification du profil avec gestion du mot de passe
     */
    public function processProfilUpdate(User $user, FormInterface $form): array
    {
        $errors = [];

        $oldPassword = $form->get('oldPassword')->getData();
        $newPassword = $form->get('newPassword')->getData();
        $confirmPassword = $form->get('confirmPassword')->getData();

        // Si des champs de mot de passe sont remplis, valider et mettre à jour
        if ($oldPassword || $newPassword || $confirmPassword) {
            $errors = $this->validatePasswordChange($user, $oldPassword, $newPassword, $confirmPassword);

            if (empty($errors)) {
                $this->updatePassword($user, $newPassword);
            } else {
                return $errors;
            }
        }

        // Sauvegarder les modifications (profil et/ou mot de passe)
        $this->saveProfil($user);

        return $errors;
    }
}
