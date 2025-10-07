<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Entity\User;
use App\Exception\SortieException;
use App\Form\CancelSortieFormType;
use App\Form\SortieFormType;
use App\Service\SortieService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class SortieController extends AbstractController
{
    public function __construct(private readonly SortieService $sortieService)
    {
    }

    #[Route('/sortie/new', name: 'app_sortie_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $sortie = new Sortie();
        $sortieForm = $this->createForm(SortieFormType::class, $sortie);
        $sortieForm->handleRequest($request);

        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            try {
                $this->sortieService->createSortie($sortie, $user);
                $this->addFlash("success", "Sortie enregistré !");
                return $this->redirectToRoute('home');
            } catch (SortieException $e) {
                $this->addFlash("error", $e->getMessage());
            }
        }

        return $this->render('sortie/new.html.twig', [
            'controller_name' => 'SortieController',
            'sortieForm' => $sortieForm->createView(),
        ]);
    }

    #[Route('/sortie/{id}', name: 'app_sortie_show', methods: ['GET'])]
    public function show(Sortie $sortie): Response
    {
        $user = $this->getUser();
        $isParticipant = $sortie->getParticipants()->contains($user);
        $isOrganisateur = $sortie->getOrganisateur() === $user;
        $nombreParticipants = $sortie->getParticipants()->count();

        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
            'isParticipant' => $isParticipant,
            'isOrganisateur' => $isOrganisateur,
            'nombreParticipants' => $nombreParticipants,
        ]);
    }

    #[Route('/sortie/{id}/cancel', name: 'app_sortie_cancel', methods: ['GET', 'POST'])]
    public function cancel(Sortie $sortie, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $cancelForm = $this->createForm(CancelSortieFormType::class);
        $cancelForm->handleRequest($request);

        if ($cancelForm->isSubmitted() && $cancelForm->isValid()) {
            try {
                $motif = $cancelForm->get('motif')->getData();
                $this->sortieService->cancel($sortie, $user, $motif);
                $this->addFlash("success", "La sortie a été annulée");
                return $this->redirectToRoute('home');
            } catch (SortieException $e) {
                $this->addFlash("error", $e->getMessage());
            }
        }
        return $this->render('sortie/cancel.html.twig', [
            'controller_name' => 'SortieController',
            'sortie' => $sortie,
            'cancelForm' => $cancelForm->createView(),
        ]);
    }

    #[Route('/sortie/{id}/inscription', name: 'app_sortie_inscription', methods: ['POST'])]
    public function inscription(Sortie $sortie, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('inscription_' . $sortie->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('home');
        }

        try {
            $this->sortieService->inscrire($sortie, $user);
            $this->addFlash('success', 'Vous êtes inscrit à la sortie : ' . $sortie->getName());
        } catch (SortieException $e) {
            $this->addFlash("error", $e->getMessage());
        }

        return $this->redirectToRoute('home');
    }

    #[Route('/sortie/{id}/delete', name: 'app_sortie_delete', methods: ['POST'])]
    public function delete(Sortie $sortie, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('delete_' . $sortie->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('home');
        }

        try {
            $this->sortieService->delete($sortie, $user);
            $this->addFlash('success', 'Vous avez supprimé la sortie : ' . $sortie->getName());
        } catch (SortieException $e) {
            $this->addFlash("error", $e->getMessage());
        }

        return $this->redirectToRoute('home');
    }

    #[Route('/sortie/{id}/publier', name: 'app_sortie_publier', methods: ['POST'])]
    public function publier(Sortie $sortie, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('publier_' . $sortie->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('home');
        }

        try {
            $this->sortieService->publier($sortie, $user);
            $this->addFlash('success', 'Vous avez publié la sortie : ' . $sortie->getName());
        } catch (SortieException $e) {
            $this->addFlash("error", $e->getMessage());
        }

        return $this->redirectToRoute('home');
    }
}
