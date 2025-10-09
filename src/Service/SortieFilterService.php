<?php

namespace App\Service;

use App\Entity\User;
use App\Form\SortieFilterType;
use App\Repository\SiteRepository;
use App\Repository\SortieRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class SortieFilterService
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly SiteRepository $siteRepository,
        private readonly SortieRepository $sortieRepository
    ) {
    }

    /**
     * Traite les filtres de recherche depuis la requête et retourne les sorties filtrées
     */
    public function processFilters(Request $request, ?User $user, int $limit, int $offset): array
    {
        $filters = [];
        $currentFilters = [];

        $isAuthenticated = $user !== null;

        $filterForm = $this->formFactory->create(SortieFilterType::class, null, [
            'is_authenticated' => $isAuthenticated
        ]);
        $filterForm->handleRequest($request);

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $this->processFormData($filterForm, $filters, $currentFilters);
        } else {
            $this->processQueryParams($request, $filters, $currentFilters);

            if (!empty($currentFilters)) {
                $filterForm = $this->createPrefilledForm($filters, $isAuthenticated);
            }
        }

        $sorties = $this->sortieRepository->findWithFilters($filters, $user, $limit, $offset);
        $totalSorties = $this->sortieRepository->countWithFilters($filters, $user);

        return [
            'sorties' => $sorties,
            'filterForm' => $filterForm,
            'currentFilters' => $currentFilters,
            'totalSorties' => $totalSorties
        ];
    }

    /**
     * Traite les données du formulaire soumis
     */
    private function processFormData(FormInterface $filterForm, array &$filters, array &$currentFilters): void
    {
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
        if (isset($formData['mesSorties']) && $formData['mesSorties']) {
            $filters['mesSorties'] = true;
            $currentFilters['mesSorties'] = '1';
        }
        if (isset($formData['sortiesInscrit']) && $formData['sortiesInscrit']) {
            $filters['sortiesInscrit'] = true;
            $currentFilters['sortiesInscrit'] = '1';
        }
        if (isset($formData['sortiesNonInscrit']) && $formData['sortiesNonInscrit']) {
            $filters['sortiesNonInscrit'] = true;
            $currentFilters['sortiesNonInscrit'] = '1';
        }
        if ($formData['sortiesPassees']) {
            $filters['sortiesPassees'] = true;
            $currentFilters['sortiesPassees'] = '1';
        }
    }

    /**
     * Traite les paramètres de l'URL (pour maintenir les filtres lors de la pagination)
     */
    private function processQueryParams(Request $request, array &$filters, array &$currentFilters): void
    {
        $queryParams = $request->query->all();

        if (isset($queryParams['site']) && $queryParams['site']) {
            $site = $this->siteRepository->find($queryParams['site']);
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
            }
        }

        if (isset($queryParams['dateFin']) && $queryParams['dateFin']) {
            try {
                $filters['dateFin'] = new \DateTime($queryParams['dateFin']);
                $currentFilters['dateFin'] = $queryParams['dateFin'];
            } catch (\Exception $e) {
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
    }

    /**
     * Crée un formulaire pré-rempli avec les filtres existants
     */
    private function createPrefilledForm(array $filters, bool $isAuthenticated): FormInterface
    {
        $formData = [];
        if (isset($filters['site'])) $formData['site'] = $filters['site'];
        if (isset($filters['name'])) $formData['name'] = $filters['name'];
        if (isset($filters['dateDebut'])) $formData['dateDebut'] = $filters['dateDebut'];
        if (isset($filters['dateFin'])) $formData['dateFin'] = $filters['dateFin'];
        if (isset($filters['mesSorties'])) $formData['mesSorties'] = $filters['mesSorties'];
        if (isset($filters['sortiesInscrit'])) $formData['sortiesInscrit'] = $filters['sortiesInscrit'];
        if (isset($filters['sortiesNonInscrit'])) $formData['sortiesNonInscrit'] = $filters['sortiesNonInscrit'];
        if (isset($filters['sortiesPassees'])) $formData['sortiesPassees'] = $filters['sortiesPassees'];

        return $this->formFactory->create(SortieFilterType::class, $formData, [
            'is_authenticated' => $isAuthenticated
        ]);
    }

    /**
     * Calcule les informations de pagination
     */
    public function calculatePagination(int $page, int $totalSorties, int $limit): array
    {
        $totalPages = ceil($totalSorties / $limit);
        $hasNext = $page < $totalPages;
        $hasPrev = $page > 1;

        return [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalSorties,
            'hasNext' => $hasNext,
            'hasPrev' => $hasPrev,
            'limit' => $limit
        ];
    }
}
