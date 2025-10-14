<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Entity\User;
use App\Entity\City;
use App\Exception\SortieException;
use App\Form\CancelSortieFormType;
use App\Form\SortieFormType;
use App\Service\SortieService;
use App\Service\StateUpdateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class SortieController extends AbstractController
{
    public function __construct(private readonly SortieService $sortieService, private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/sortie/new', name: 'app_sortie_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        /** @var User $user */
        $userAgent = $request->headers->get('User-Agent');
        $user = $this->getUser();
        $sortie = new Sortie();
        $sortieForm = $this->createForm(SortieFormType::class, $sortie);
        $sortieForm->handleRequest($request);

        if (preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
            $this->addFlash('danger', 'La création de sortie n\'est pas disponible sur mobile.');
            return $this->redirectToRoute('home');
        }

        if ($sortieForm->isSubmitted()) {
            if (!$sortieForm->isValid()) {
                // Afficher les erreurs du formulaire
                foreach ($sortieForm->getErrors(true) as $error) {
                    $this->addFlash("error", $error->getMessage());
                }
            } else {
                try {
                    $isActionPublish = $request->request->get('action') === 'publier';
                    $this->sortieService->createSortie($sortie, $user, $isActionPublish);
                    $message = $isActionPublish ? "Sortie publiée avec succès !" : "Sortie enregistrée !";
                    $this->addFlash("success", $message);
                    return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
                } catch (\Exception $e) {
                    $this->addFlash("error", "Erreur lors de la création : " . $e->getMessage());
                }
            }
        }

        return $this->render('sortie/new.html.twig', [
            'controller_name' => 'SortieController',
            'sortieForm' => $sortieForm->createView(),
        ]);
    }

    #[Route('/sortie/{id}', name: 'app_sortie_show', methods: ['GET'])]
    public function show(Sortie $sortie, StateUpdateService $stateUpdateService): Response
    {
        // Mise à jour automatique du statut de cette sortie
        $stateUpdateService->updateSortieState($sortie);

        $user = $this->getUser();
        $isParticipant = $sortie->getParticipants()->contains($user);
        $isOrganisateur = $sortie->getOrganisateur() === $user;
        $nombreParticipants = $sortie->getParticipants()->count();

        // Vérifier si la date de clôture des inscriptions est dépassée
        $now = new \DateTime();
        $inscriptionsCloturees = $sortie->getRegistrationDeadline() < $now;

        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
            'isParticipant' => $isParticipant,
            'isOrganisateur' => $isOrganisateur,
            'nombreParticipants' => $nombreParticipants,
            'inscriptionsCloturees' => $inscriptionsCloturees,
        ]);
    }

    #[Route('/sortie/{id}/edit', name: 'app_sortie_edit', methods: ['GET', 'POST'])]
    public function edit(Sortie $sortie, StateUpdateService $stateUpdateService, Request $request): Response
    {
        // Mise à jour automatique du statut de cette sortie
        $stateUpdateService->updateSortieState($sortie);

        // Utilisation du Voter pour vérifier les permissions
        $this->denyAccessUnlessGranted('SORTIE_EDIT', $sortie);

        /** @var User $user */
        $user = $this->getUser();

        $sortieForm = $this->createForm(SortieFormType::class, $sortie);
        if ($sortie->getPlace() && $sortie->getPlace()->getCity()) {
            $sortieForm->get('city')->setData($sortie->getPlace()->getCity());
        }

        $sortieForm->handleRequest($request);

        if ($sortieForm->isSubmitted()) {
            if (!$sortieForm->isValid()) {
                foreach ($sortieForm->getErrors(true) as $error) {
                    $this->addFlash("error", $error->getMessage());
                    return $this->redirectToRoute('app_sortie_edit', ['id' => $sortie->getId()]);
                }
            } else {
                try {
                    $this->sortieService->edit($sortie, $user);
                    $this->addFlash("success", "Sortie modifié avec succès");
                    return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
                } catch (SortieException $e) {
                    $this->addFlash("error", $e->getMessage());
                    return $this->redirectToRoute('app_sortie_edit', ['id' => $sortie->getId()]);
                }
            }
        }

        return $this->render('sortie/new.html.twig', [
            'sortie' => $sortie,
            'sortieForm' => $sortieForm->createView(),
        ]);
    }

    #[Route('/sortie/{id}/cancel', name: 'app_sortie_cancel', methods: ['GET', 'POST'])]
    public function cancel(Sortie $sortie, Request $request): Response
    {
        // Utilisation du Voter pour vérifier les permissions
        $this->denyAccessUnlessGranted('SORTIE_CANCEL', $sortie);

        /** @var User $user */
        $user = $this->getUser();
        $isOrganisateur = $sortie->getOrganisateur() === $user;
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);

        $cancelForm = $this->createForm(CancelSortieFormType::class);
        $cancelForm->handleRequest($request);

        if ($cancelForm->isSubmitted() && $cancelForm->isValid()) {
            try {
                $motif = $cancelForm->get('motif')->getData();
                $this->sortieService->cancel($sortie, $user, $motif);
                $this->addFlash("success", "La sortie a été annulée");
                return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
            } catch (SortieException $e) {
                $this->addFlash("error", $e->getMessage());
            }
        }
        return $this->render('sortie/cancel.html.twig', [
            'controller_name' => 'SortieController',
            'sortie' => $sortie,
            'cancelForm' => $cancelForm->createView(),
            'isOrganisateur' => $isOrganisateur,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/sortie/{id}/inscription', name: 'app_sortie_inscription', methods: ['POST'])]
    public function inscription(Sortie $sortie, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('inscription_' . $sortie->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');
        }

        // Utilisation du Voter pour vérifier les permissions
        if (!$this->isGranted('SORTIE_REGISTER', $sortie)) {
            $this->addFlash('error', 'Vous ne pouvez pas vous inscrire à cette sortie');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        try {
            $this->sortieService->inscrire($sortie, $user);
            $this->addFlash('success', 'Vous êtes inscrit à la sortie : ' . $sortie->getName());
        } catch (SortieException $e) {
            $this->addFlash("error", $e->getMessage());
        }

        return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
    }

    #[Route('/sortie/{id}/desinscrire', name: 'app_sortie_desinscrire', methods: ['POST'])]
    public function desinscrire(Sortie $sortie, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('desinscrire_' . $sortie->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');
        }

        // Utilisation du Voter pour vérifier les permissions
        if (!$this->isGranted('SORTIE_UNREGISTER', $sortie)) {
            $this->addFlash('error', 'Vous ne pouvez pas vous désinscrire de cette sortie');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        try {
            $this->sortieService->desinscrire($sortie, $user);
            $this->addFlash('success', 'Vous êtes désinscris de la sortie : ' . $sortie->getName());
        } catch (SortieException $e) {
            $this->addFlash("error", $e->getMessage());
        }

        return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
    }

    #[Route('/sortie/{id}/delete', name: 'app_sortie_delete', methods: ['POST'])]
    public function delete(Sortie $sortie, Request $request): Response
    {
        // Utilisation du Voter pour vérifier les permissions
        $this->denyAccessUnlessGranted('SORTIE_DELETE', $sortie);

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
        // Utilisation du Voter pour vérifier les permissions
        $this->denyAccessUnlessGranted('SORTIE_PUBLISH', $sortie);

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('publier_' . $sortie->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        try {
            $this->sortieService->publier($sortie, $user);
            $this->addFlash('success', 'Vous avez publié la sortie : ' . $sortie->getName());
        } catch (SortieException $e) {
            $this->addFlash("error", $e->getMessage());
        }

        return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
    }

    #[Route('/places/by-city/{id}', name: 'places_by_city', methods: ['GET'])]
    public function getPlacesByCity(City $city): JsonResponse
    {
        $places = $city->getPlaces();
        $data = [];

        foreach ($places as $place) {
            $data[] = [
                'id' => $place->getId(),
                'name' => $place->getName(),
                'street' => $place->getStreet(),
                'latitude' => $place->getLatitude(),
                'longitude' => $place->getLongitude(),
                // Champs nécessaires au front
                'cityName' => $place->getCity() ? $place->getCity()->getName() : null,
                'postalCode' => $place->getCity() ? $place->getCity()->getPostalCode() : null,
            ];
        }

        return $this->json($data);
    }

}
