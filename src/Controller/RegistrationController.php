<?php
// src/Controller/RegistrationController.php
namespace App\Controller;

use App\Repository\SiteRepository;
use App\Service\UserRegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    /**
     * Affiche et traite le formulaire d'inscription
     */
    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        UserRegistrationService $registrationService,
        SiteRepository $siteRepo
    ): Response {
        if ($request->isMethod('POST')) {
            $data = $registrationService->extractRegistrationData($request->request->all());

            $errors = $registrationService->validateRegistrationData($data);

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_register');
            }

            try {
                $registrationService->createUser($data);
                $this->addFlash('success', 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la création du compte.');
                return $this->redirectToRoute('app_register');
            }
        }

        $sites = $siteRepo->findAll();

        return $this->render('security/register.html.twig', [
            'sites' => $sites,
        ]);
    }
}
