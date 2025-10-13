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
     *
     * Gère la soumission du formulaire de filtres ainsi que les paramètres d'URL
     * pour maintenir les filtres lors de la pagination.
     *
     * @param Request $request La requête HTTP contenant les filtres
     * @param User|null $user L'utilisateur connecté (null si non authentifié)
     * @param int $limit Nombre maximum de sorties à retourner
     * @param int $offset Décalage pour la pagination
     *
     * @return array{sorties: array, filterForm: FormInterface, currentFilters: array, totalSorties: int}
     *               Tableau contenant les sorties filtrées, le formulaire, les filtres actifs et le total
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
     *
     * Extrait les données du formulaire et les transforme en filtres exploitables
     * pour la recherche de sorties.
     *
     * @param FormInterface $filterForm Le formulaire de filtres soumis
     * @param array $filters Tableau des filtres (passé par référence)
     * @param array $currentFilters Tableau des filtres actuels pour l'affichage (passé par référence)
     *
     * @return void
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
     *
     * Récupère les paramètres GET de l'URL et les transforme en filtres pour maintenir
     * l'état des filtres lors de la navigation entre pages.
     *
     * @param Request $request La requête HTTP
     * @param array $filters Tableau des filtres (passé par référence)
     * @param array $currentFilters Tableau des filtres actuels pour l'affichage (passé par référence)
     *
     * @return void
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
     *
     * Génère un nouveau formulaire avec les valeurs des filtres actuels
     * pour maintenir l'état du formulaire.
     *
     * @param array $filters Les filtres à appliquer au formulaire
     * @param bool $isAuthenticated Indique si l'utilisateur est authentifié
     *
     * @return FormInterface Le formulaire pré-rempli
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
     *
     * Détermine le nombre total de pages, la page actuelle et la présence
     * de pages suivantes/précédentes pour la navigation.
     *
     * @param int $page Numéro de la page actuelle
     * @param int $totalSorties Nombre total de sorties
     * @param int $limit Nombre de sorties par page
     *
     * @return array{currentPage: int, totalPages: int, totalItems: int, hasNext: bool, hasPrev: bool, limit: int}
     *               Informations de pagination
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
