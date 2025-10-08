<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminUserFormType;
use App\Form\UserImportFormType;
use App\Repository\UserRepository;
use App\Service\UserImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/users', name: 'admin_users_list')]
    public function listUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/user_list.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/create', name: 'admin_users_create')]
    public function createUser(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $user = new User();
        $user->setActive(true); // Actif par défaut

        $form = $this->createForm(AdminUserFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hasher le mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', sprintf('L\'utilisateur "%s" a été créé avec succès.', $user->getPseudo()));

            return $this->redirectToRoute('admin_users_list');
        }

        return $this->render('admin/user_create.html.twig', [
            'form' => $form->createView(),
        ], new Response(
            null,
            $form->isSubmitted() && !$form->isValid() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        ));
    }

    #[Route('/users/{id}/edit', name: 'admin_users_edit', requirements: ['id' => '\d+'])]
    public function editUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $form = $this->createForm(AdminUserFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hasher le mot de passe seulement si un nouveau mot de passe est fourni
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', sprintf('L\'utilisateur "%s" a été modifié avec succès.', $user->getPseudo()));

            return $this->redirectToRoute('admin_users_list');
        }

        return $this->render('admin/user_edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ], new Response(
            null,
            $form->isSubmitted() && !$form->isValid() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        ));
    }

    #[Route('/users/{id}/delete', name: 'admin_users_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteUser(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $pseudo = $user->getPseudo();
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', sprintf('L\'utilisateur "%s" a été supprimé avec succès.', $pseudo));
        }

        return $this->redirectToRoute('admin_users_list');
    }

    #[Route('/users/import', name: 'admin_users_import')]
    public function importUsers(Request $request, UserImportService $userImportService): Response
    {
        $form = $this->createForm(UserImportFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('csv_file')->getData();

            if ($file) {
                try {
                    $result = $userImportService->importUsers($file);
                    if (!empty($result['errors'])) {
                        foreach ($result['errors'] as $error) {
                            $this->addFlash('danger', $error);
                        }
                    } else {
                        $this->addFlash('success', sprintf('%d utilisateurs ont été importés avec succès.', $result['successful']));
                    }
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Une erreur est survenue lors du traitement du fichier : ' . $e->getMessage());
                }
            }

            return $this->redirectToRoute('admin_users_import');
        }

        return $this->render('admin/user_import.html.twig', [
            'form' => $form->createView(),
        ], new Response(
            null,
            $form->isSubmitted() && !$form->isValid() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        ));
    }
}
