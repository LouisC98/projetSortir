<?php

namespace App\Controller;

use App\Form\UserImportFormType;
use App\Service\UserImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
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
