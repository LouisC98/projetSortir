<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Sortie;
use App\Form\RatingType;
use App\Repository\RatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rating')]
#[IsGranted('ROLE_USER')]
class RatingController extends AbstractController
{
    #[Route('/sortie/{id}/rate', name: 'app_rating_create', methods: ['GET', 'POST'])]
    public function create(
        Sortie $sortie,
        Request $request,
        EntityManagerInterface $em,
        RatingRepository $ratingRepository
    ): Response {
        $user = $this->getUser();

        // Vérifier que la sortie est terminée
        $now = new \DateTime();
        $endDateTime = (clone $sortie->getStartDateTime())->modify('+' . $sortie->getDuration() . ' minutes');

        if ($endDateTime > $now) {
            $this->addFlash('error', 'Vous ne pouvez noter une sortie qu\'après qu\'elle soit terminée.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        // Vérifier que l'utilisateur a participé
        if (!$sortie->getParticipants()->contains($user)) {
            $this->addFlash('error', 'Seuls les participants peuvent noter cette sortie.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        // Vérifier si l'utilisateur a déjà noté
        $existingRating = $ratingRepository->getUserRating($user, $sortie);

        if ($existingRating) {
            $rating = $existingRating;
            $isEdit = true;
        } else {
            $rating = new Rating();
            $rating->setUser($user);
            $rating->setSortie($sortie);
            $isEdit = false;
        }

        $form = $this->createForm(RatingType::class, $rating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$isEdit) {
                $em->persist($rating);
            }
            $em->flush();

            $this->addFlash('success', $isEdit ? 'Votre note a été modifiée avec succès.' : 'Merci pour votre note !');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        return $this->render('rating/form.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
            'isEdit' => $isEdit,
        ]);
    }

    #[Route('/sortie/{id}/ratings', name: 'app_rating_list', methods: ['GET'])]
    public function list(Sortie $sortie, RatingRepository $ratingRepository): Response
    {
        $ratings = $ratingRepository->findBySortieWithUsers($sortie);
        $averageRating = $ratingRepository->getAverageRating($sortie);
        $totalRatings = $ratingRepository->countRatings($sortie);

        return $this->render('rating/list.html.twig', [
            'sortie' => $sortie,
            'ratings' => $ratings,
            'averageRating' => $averageRating,
            'totalRatings' => $totalRatings,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_rating_delete', methods: ['POST'])]
    public function delete(
        Rating $rating,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        if ($rating->getUser() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres notes.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $rating->getSortie()->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $rating->getId(), $request->request->get('_token'))) {
            $sortieId = $rating->getSortie()->getId();
            $em->remove($rating);
            $em->flush();

            $this->addFlash('success', 'Votre note a été supprimée.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortieId]);
        }

        return $this->redirectToRoute('app_sortie_show', ['id' => $rating->getSortie()->getId()]);
    }
}

