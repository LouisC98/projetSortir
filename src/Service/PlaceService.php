<?php

namespace App\Service;

use App\Entity\Place;
use App\Repository\CityRepository;
use App\Repository\PlaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PlaceService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PlaceRepository $placeRepository,
        private CityRepository $cityRepository,
        private ValidatorInterface $validator,
        private ValidationService $validationService
    ) {}

    /**
     * Crée un nouveau lieu après validation des données
     *
     * Vérifie que le lieu n'existe pas déjà (nom + rue + ville) et valide
     * les contraintes métier avant la création.
     *
     * @param string $name Le nom du lieu
     * @param string $street L'adresse de la rue
     * @param string|null $latitude La latitude GPS (optionnelle, entre -90 et 90)
     * @param string|null $longitude La longitude GPS (optionnelle, entre -180 et 180)
     * @param int $cityId L'identifiant de la ville associée
     *
     * @return array{success: bool, message?: string, data?: array{id: int, name: string, street: string, latitude: ?float, longitude: ?float, cityName: string, postalCode: string}}
     *               Tableau contenant le résultat de l'opération avec les données du lieu créé en cas de succès
     */
    public function createPlace(
        string $name,
        string $street,
        ?string $latitude,
        ?string $longitude,
        int $cityId
    ): array {
        $name = trim($name);
        $street = trim($street);
        $latitude = $latitude ? trim($latitude) : null;
        $longitude = $longitude ? trim($longitude) : null;

        // Validation métier
        $error = $this->validationService->validatePlace($name, $street, $latitude, $longitude);
        if ($error) {
            return ['success' => false, 'message' => $error];
        }

        $city = $this->cityRepository->find($cityId);
        if (!$city) {
            return ['success' => false, 'message' => 'Ville introuvable'];
        }

        // Vérification de doublon
        $existingPlace = $this->placeRepository->findOneBy([
            'name' => $name,
            'street' => $street,
            'city' => $city
        ]);

        if ($existingPlace) {
            return ['success' => false, 'message' => 'Ce lieu existe déjà dans cette ville'];
        }

        $latitude = ($latitude !== null && $latitude !== '') ? $latitude : null;
        $longitude = ($longitude !== null && $longitude !== '') ? $longitude : null;

        $place = new Place();
        $place->setName($name);
        $place->setStreet($street);
        if ($latitude !== null) {
            $place->setLatitude((float) $latitude);
        }
        if ($longitude !== null) {
            $place->setLongitude((float) $longitude);
        }
        $place->setCity($city);

        $errors = $this->validator->validate($place);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return ['success' => false, 'message' => implode(', ', $errorMessages)];
        }

        $this->entityManager->persist($place);
        $this->entityManager->flush();

        return [
            'success' => true,
            'data' => [
                'id' => $place->getId(),
                'name' => $place->getName(),
                'street' => $place->getStreet(),
                'latitude' => $place->getLatitude(),
                'longitude' => $place->getLongitude(),
                'cityName' => $place->getCity()->getName(),
                'postalCode' => $place->getCity()->getPostalCode()
            ]
        ];
    }

    /**
     * Récupère tous les lieux d'une ville donnée
     *
     * @param int $cityId L'identifiant de la ville
     *
     * @return array{success: bool, message?: string, data?: array<array{id: int, name: string, street: string, latitude: ?float, longitude: ?float}>}
     *               Tableau contenant le résultat avec la liste des lieux en cas de succès
     */
    public function getPlacesByCity(int $cityId): array
    {
        $city = $this->cityRepository->find($cityId);
        if (!$city) {
            return ['success' => false, 'message' => 'Ville introuvable'];
        }

        $places = $this->placeRepository->findBy(['city' => $city]);

        $result = array_map(function (Place $place) {
            $placeData = [
                'id' => $place->getId(),
                'name' => $place->getName(),
                'street' => $place->getStreet(),
                'latitude' => $place->getLatitude(),
                'longitude' => $place->getLongitude(),
                'cityName' => $place->getCity()->getName(),
                'postalCode' => $place->getCity()->getPostalCode()
            ];
            return $placeData;
        }, $places);

        return ['success' => true, 'data' => $result];
    }

}
