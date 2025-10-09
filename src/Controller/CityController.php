<?php

namespace App\Controller;

use App\Service\CityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CityController extends AbstractController
{
    public function __construct(
        private CityService $cityService
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
}
