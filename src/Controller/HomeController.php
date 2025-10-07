<?php

namespace App\Controller;

use App\Form\SortieFilterType;
use App\Repository\SiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\SortieRepository;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, SortieRepository $sortieRepository, SiteRepository $siteRepository): Response
    {
        // Paramètres de pagination
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        // Initialiser les filtres
        $filters = [];
        $currentFilters = [];

        // Créer le formulaire de filtres
        $filterForm = $this->createForm(SortieFilterType::class);
        $filterForm->handleRequest($request);

        // Traitement des filtres
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // Formulaire soumis - récupérer les données du formulaire
            $formData = $filterForm->getData();

            if ($formData['site']) {
                $filters['site'] = $formData['site'];
                $currentFilters['site'] = $formData['site']->getId();
            }
            if ($formData['name']) {
                $filters['name'] = $formData['name'];
                $currentFilters['name'] = $formData['name'];
            }
            if ($formData['dateDebut']) {
                $filters['dateDebut'] = $formData['dateDebut'];
                $currentFilters['dateDebut'] = $formData['dateDebut']->format('Y-m-d');
            }
            if ($formData['dateFin']) {
                $filters['dateFin'] = $formData['dateFin'];
                $currentFilters['dateFin'] = $formData['dateFin']->format('Y-m-d');
            }
            if ($formData['mesSorties']) {
                $filters['mesSorties'] = true;
                $currentFilters['mesSorties'] = '1';
            }
            if ($formData['sortiesInscrit']) {
                $filters['sortiesInscrit'] = true;
                $currentFilters['sortiesInscrit'] = '1';
            }
            if ($formData['sortiesNonInscrit']) {
                $filters['sortiesNonInscrit'] = true;
                $currentFilters['sortiesNonInscrit'] = '1';
            }
            if ($formData['sortiesPassees']) {
                $filters['sortiesPassees'] = true;
                $currentFilters['sortiesPassees'] = '1';
            }
        } else {
            // Récupération depuis les paramètres GET (pagination ou lien direct)
            $queryParams = $request->query->all();

            if (isset($queryParams['site']) && $queryParams['site']) {
                $site = $siteRepository->find($queryParams['site']);
                if ($site) {
                    $filters['site'] = $site;
                    $currentFilters['site'] = $queryParams['site'];
                }
            }

            if (isset($queryParams['name']) && $queryParams['name']) {
                $filters['name'] = $queryParams['name'];
                $currentFilters['name'] = $queryParams['name'];
            }

            if (isset($queryParams['dateDebut']) && $queryParams['dateDebut']) {
                try {
                    $filters['dateDebut'] = new \DateTime($queryParams['dateDebut']);
                    $currentFilters['dateDebut'] = $queryParams['dateDebut'];
                } catch (\Exception $e) {
                    // Date invalide, ignorer
                }
            }

            if (isset($queryParams['dateFin']) && $queryParams['dateFin']) {
                try {
                    $filters['dateFin'] = new \DateTime($queryParams['dateFin']);
                    $currentFilters['dateFin'] = $queryParams['dateFin'];
                } catch (\Exception $e) {
                    // Date invalide, ignorer
                }
            }

            if (isset($queryParams['mesSorties']) && $queryParams['mesSorties'] === '1') {
                $filters['mesSorties'] = true;
                $currentFilters['mesSorties'] = '1';
            }

            if (isset($queryParams['sortiesInscrit']) && $queryParams['sortiesInscrit'] === '1') {
                $filters['sortiesInscrit'] = true;
                $currentFilters['sortiesInscrit'] = '1';
            }

            if (isset($queryParams['sortiesNonInscrit']) && $queryParams['sortiesNonInscrit'] === '1') {
                $filters['sortiesNonInscrit'] = true;
                $currentFilters['sortiesNonInscrit'] = '1';
            }

            if (isset($queryParams['sortiesPassees']) && $queryParams['sortiesPassees'] === '1') {
                $filters['sortiesPassees'] = true;
                $currentFilters['sortiesPassees'] = '1';
            }

            // Pré-remplir le formulaire avec les valeurs des paramètres GET
            if (!empty($currentFilters)) {
                $formData = [];
                if (isset($filters['site'])) $formData['site'] = $filters['site'];
                if (isset($filters['name'])) $formData['name'] = $filters['name'];
                if (isset($filters['dateDebut'])) $formData['dateDebut'] = $filters['dateDebut'];
                if (isset($filters['dateFin'])) $formData['dateFin'] = $filters['dateFin'];
                if (isset($filters['mesSorties'])) $formData['mesSorties'] = $filters['mesSorties'];
                if (isset($filters['sortiesInscrit'])) $formData['sortiesInscrit'] = $filters['sortiesInscrit'];
                if (isset($filters['sortiesNonInscrit'])) $formData['sortiesNonInscrit'] = $filters['sortiesNonInscrit'];
                if (isset($filters['sortiesPassees'])) $formData['sortiesPassees'] = $filters['sortiesPassees'];

                $filterForm = $this->createForm(SortieFilterType::class, $formData);
            }
        }

        // Récupérer les sorties avec filtres
        $sorties = $sortieRepository->findWithFilters($filters, $user, $limit, $offset);
        $totalSorties = $sortieRepository->countWithFilters($filters, $user);

        // Calculer les informations de pagination
        $totalPages = ceil($totalSorties / $limit);
        $hasNext = $page < $totalPages;
        $hasPrev = $page > 1;

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'sorties' => $sorties,
            'filterForm' => $filterForm->createView(),
            'currentFilters' => $currentFilters,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalSorties,
                'hasNext' => $hasNext,
                'hasPrev' => $hasPrev,
                'limit' => $limit
            ]
        ]);
    }
}
