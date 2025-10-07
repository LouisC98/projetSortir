<?php

namespace App\Controller;

use App\Form\EditProfilType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'view_profil')]
    public function viewProfil(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(EditProfilType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $oldPassword = $form->get('oldPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            // Si un changement de mot de passe est demandé
            if ($oldPassword || $newPassword || $confirmPassword) {
                if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
                    $this->addFlash('error', 'L’ancien mot de passe est incorrect.');
                    return $this->redirectToRoute('view_profil');
                }

                if ($newPassword !== $confirmPassword) {
                    $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
                    return $this->redirectToRoute('view_profil');
                }

                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            }

            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès !');
            return $this->redirectToRoute('view_profil');
        }

        return $this->render('profil/view_profil.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
