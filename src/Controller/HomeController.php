<?php

namespace App\Controller;

use App\Service\SortieFilterService;
use App\Service\StateUpdateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        Request $request,
        SortieFilterService $sortieFilterService,
        StateUpdateService $stateUpdateService
    ): Response
    {
        $stateUpdateService->updateAllStates();

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        $user = $this->getUser();

        $result = $sortieFilterService->processFilters($request, $user, $limit, $offset);

        $pagination = $sortieFilterService->calculatePagination($page, $result['totalSorties'], $limit);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'sorties' => $result['sorties'],
            'filterForm' => $result['filterForm']->createView(),
            'currentFilters' => $result['currentFilters'],
            'pagination' => $pagination
        ]);
    }
}
