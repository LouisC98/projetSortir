<?php

namespace App\Controller;

use App\Service\PlaceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PlaceController extends AbstractController
{
    public function __construct(
        private PlaceService $placeService
    ) {}

    #[Route('/place/new', name: 'place_new', methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data || !isset($data['name']) || !isset($data['street']) || !isset($data['cityId'])) {
                return $this->json([
                    'message' => 'Données invalides'
                ], Response::HTTP_BAD_REQUEST);
            }

            $result = $this->placeService->createPlace(
                $data['name'],
                $data['street'],
                $data['latitude'] ?? null,
                $data['longitude'] ?? null,
                $data['cityId']
            );

            if (!$result['success']) {
                $statusCode = match ($result['message']) {
                    'Ville introuvable' => Response::HTTP_NOT_FOUND,
                    'Ce lieu existe déjà dans cette ville' => Response::HTTP_CONFLICT,
                    default => Response::HTTP_BAD_REQUEST
                };

                return $this->json([
                    'message' => $result['message']
                ], $statusCode);
            }

            return $this->json($result['data'], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de l\'ajout du lieu'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
