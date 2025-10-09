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

    public function getPlacesByCity(int $cityId): array
    {
        // MODIFICATION FORCEE POUR FORCER LE RECHARGEMENT - 2025-10-09
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
