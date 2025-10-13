<?php

namespace App\Controller;

use App\Service\CityService;
use App\Service\GeoApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CityController extends AbstractController
{
    public function __construct(
        private CityService $cityService,
        private GeoApiService $geoApiService
    ) {}

    #[Route('/city/new', name: 'city_new', methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data || !isset($data['name']) || !isset($data['postalCode'])) {
                return $this->json([
                    'message' => 'Données invalides'
                ], Response::HTTP_BAD_REQUEST);
            }

            $result = $this->cityService->createCity($data['name'], $data['postalCode']);

            if (!$result['success']) {
                $statusCode = match ($result['message']) {
                    'Cette ville existe déjà' => Response::HTTP_CONFLICT,
                    default => Response::HTTP_BAD_REQUEST
                };

                return $this->json([
                    'message' => $result['message']
                ], $statusCode);
            }

            return $this->json($result['data'], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de l\'ajout de la ville'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/cities/search', name: 'api_cities_search', methods: ['GET'])]
    public function searchCities(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = $request->query->getInt('limit', 10);

        if (strlen($query) < 2) {
            return $this->json([]);
        }

        try {
            $cities = $this->geoApiService->searchCities($query, $limit);
            $formattedCities = $this->geoApiService->formatCitiesForAutocomplete($cities);

            return $this->json($formattedCities);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la recherche de villes'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/cities/coordinates', name: 'api_cities_coordinates', methods: ['GET'])]
    public function getCityCoordinates(Request $request): JsonResponse
    {
        $cityName = $request->query->get('city', '');
        $postalCode = $request->query->get('postalCode', null);

        if (empty($cityName)) {
            return $this->json([
                'error' => 'Le nom de la ville est requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $coordinates = $this->geoApiService->getCityCoordinates($cityName, $postalCode);

            if ($coordinates === null) {
                return $this->json([
                    'error' => 'Ville non trouvée'
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->json($coordinates);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la récupération des coordonnées'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
