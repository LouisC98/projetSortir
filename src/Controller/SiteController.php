<?php

namespace App\Controller;

use App\Entity\Site;
use App\Form\SiteFormType;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted("ROLE_ADMIN")]
#[Route('/admin/site')]
final class SiteController extends AbstractController
{
    public function __construct(private readonly SiteRepository $siteRepository, private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/', name: 'app_site_list', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('site/list.html.twig', [
            'sites' => $this->siteRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_site_new', methods: ['GET','POST'])]
    public function new(Request $request): Response
    {
        $site = new Site();
        $siteForm = $this->createForm(SiteFormType::class, $site);
        $siteForm->handleRequest($request);

        if ($siteForm->isSubmitted() && $siteForm->isValid()) {
            $this->entityManager->persist($site);
            $this->entityManager->flush();
            $this->addFlash("success", "Site ajouté avec succès");
            return $this->redirectToRoute('app_site_list');
        }

        return $this->render('site/new.html.twig', [
            'siteForm' => $siteForm->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_site_edit', methods: ['GET','POST'])]
    public function edit(Site $site, Request $request): Response
    {
        $siteForm = $this->createForm(SiteFormType::class, $site);
        $siteForm->handleRequest($request);

        if ($siteForm->isSubmitted() && $siteForm->isValid()) {
            $this->entityManager->persist($site);
            $this->entityManager->flush();
            $this->addFlash("success", "Site modifié avec succès");
            return $this->redirectToRoute('app_site_list');
        }

        return $this->render('site/new.html.twig', [
            'siteForm' => $siteForm->createView(),
            'site' => $site,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_site_delete', methods: ['POST'])]
    public function delete(Site $site, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete_'.$site->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($site);
            $this->entityManager->flush();
            $this->addFlash("success", "Site supprimé avec succès");
            return $this->redirectToRoute('app_site_list');
        }

        $this->addFlash("error", "Erreur lors de la suppression");
        return $this->redirectToRoute('app_site_list');
    }

}
