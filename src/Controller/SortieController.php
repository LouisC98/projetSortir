<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Enum\State;
use App\Form\CancelSortieFormType;
use App\Form\SortieFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SortieController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/sortie/new', name: 'app_sortie_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $sortie = new Sortie();
        $sortieForm = $this->createForm(SortieFormType::class, $sortie);
        $sortieForm->handleRequest($request);

        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            // Vérification de la date limite d'inscription
            if ($sortie->getRegistrationDeadline() > $sortie->getStartDateTime()) {
                $this->addFlash("error", "La date limite d'inscription doit être avant la date de début de la sortie !");
                return $this->render('sortie/new.html.twig', [
                    'controller_name' => 'SortieController',
                    'sortieForm' => $sortieForm->createView(),
                ]);
            }

            $sortie->setState(State::CREATED);
            $user = $this->getUser();
            $sortie->setSite($user->getSite());
            $sortie->setOrganisateur($user);
            $this->entityManager->persist($sortie);
            $this->entityManager->flush();

            $this->addFlash("success","Sortie enregistré !");
            return $this->redirectToRoute('home');
        }

        return $this->render('sortie/new.html.twig', [
            'controller_name' => 'SortieController',
            'sortieForm' => $sortieForm->createView(),
        ]);
    }

    #[Route('/sortie/{id}/cancel', name: 'app_sortie_cancel', methods: ['GET', 'POST'])]
    public function cancel(Sortie $sortie, Request $request): Response
    {
//        VERIF ORGANISATEUR
        $user = $this->getUser();
        $organisateur = $sortie->getOrganisateur();
        if ($organisateur->getId() !== $user->getId()) {
            $this->addFlash("danger","Vous n'êtes pas l'organisateur de la sortie");
            return $this->redirectToRoute('home');
        }

//        VERIF DATE DE DEBUT
        $startDateTime = $sortie->getStartDateTime();
        $now = new \DateTime();
        if ($startDateTime < $now) {
            $this->addFlash("danger","Sortie déjà commencé");
            return $this->redirectToRoute('home');
        }

        $cancelForm = $this->createForm(CancelSortieFormType::class);
        $cancelForm->handleRequest($request);
        if ($cancelForm->isSubmitted() && $cancelForm->isValid()) {
            $sortie->setState(State::CANCELLED);

//            AJOUT DU MOTIF DEVANT LA DESCRiPION
            $motif = $cancelForm->get('motif')->getData();
            $description = $sortie->getDescription();
            $nouveauContenu = sprintf(
                "=== SORTIE ANNULÉE ===\nMotif : %s\n\n==========\n%s",
                $motif,
                $description
            );
            $sortie->setDescription($nouveauContenu);


            $this->entityManager->persist($sortie);
            $this->entityManager->flush();

            return $this->redirectToRoute('home');
        }
        return $this->render('sortie/cancel.html.twig', [
            'controller_name' => 'SortieController',
            'sortie' => $sortie,
            'cancelForm' => $cancelForm->createView(),
        ]);
    }
}
