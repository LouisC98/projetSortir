<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditProfilType;
use App\Repository\CommentRepository;
use App\Service\ProfilService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfilController extends AbstractController
{
    /**
     * Affiche le profil de l'utilisateur connecté
     */
    #[Route('/profil', name: 'view_profil')]
    #[IsGranted('ROLE_USER')]
    public function viewProfil(CommentRepository $commentRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $comments = $commentRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('profil/view_profil.html.twig', [
            'user' => $user,
            'comments' => $comments
        ]);
    }

    /**
     * Permet à l'utilisateur de modifier son profil
     */
    #[Route('/profil/edit', name: 'edit_profil')]
    #[IsGranted('ROLE_USER')]
    public function editProfil(
        Request $request,
        ProfilService $profilService
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(EditProfilType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $profilService->processProfilUpdate($user, $form);

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('edit_profil');
            }

            $this->addFlash('success', 'Profil mis à jour avec succès !');
            return $this->redirectToRoute('view_profil');
        }

        return $this->render('profil/edit_profil.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche le profil public d'un utilisateur
     */
    #[Route('/profil/{id}', name: 'view_user_profil', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function viewUserProfil(User $user, CommentRepository $commentRepository): Response
    {
        if ($user === $this->getUser()) {
            return  $this->redirectToRoute('view_profil');
        }
        $comments = $commentRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        return $this->render('profil/view_user_profil.html.twig', [
            'user' => $user,
            'comments' => $comments
        ]);
    }
}
