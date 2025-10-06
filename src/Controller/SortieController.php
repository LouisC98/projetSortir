<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Enum\State;
use App\Form\SortieFormType;
use App\Repository\SiteRepository;
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
    public function new(Request $request, SiteRepository $siteRepository): Response
    {
        $sortie = new Sortie();
        $sortieForm = $this->createForm(SortieFormType::class, $sortie);
        $sortieForm->handleRequest($request);

        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            $sortie->setState(State::CREATED);
//            A CHANGER APRES USER / CONNEXION / ETC
            $sortie->setSite($siteRepository->findAll()[array_rand($siteRepository->findAll())]);
//            A CHANGER APRES USER / CONNEXION / ETC
            $this->entityManager->persist($sortie);
            $this->entityManager->flush();

            $this->addFlash("success","Sortie enregistré !");
            return $this->redirectToRoute('app_sortie_new');
        }

        return $this->render('sortie/new.html.twig', [
            'controller_name' => 'SortieController',
            'sortieForm' => $sortieForm->createView(),
        ]);
    }
}
