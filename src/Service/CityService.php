<?php

namespace App\Service;

use App\Entity\City;
use App\Repository\CityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CityService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CityRepository $cityRepository,
        private ValidatorInterface $validator,
        private ValidationService $validationService
    ) {}

    public function createCity(string $name, string $postalCode): array
    {
        $name = trim($name);
        $postalCode = trim($postalCode);

        // Validation métier
        $error = $this->validationService->validateCity($name, $postalCode);
        if ($error) {
            return ['success' => false, 'message' => $error];
        }

        // Vérification de doublon
        $existingCity = $this->cityRepository->findOneBy([
            'name' => $name,
            'postalCode' => $postalCode
        ]);

        if ($existingCity) {
            return ['success' => false, 'message' => 'Cette ville existe déjà'];
        }

        $city = new City();
        $city->setName($name);
        $city->setPostalCode($postalCode);

        $errors = $this->validator->validate($city);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return ['success' => false, 'message' => implode(', ', $errorMessages)];
        }

        $this->entityManager->persist($city);
        $this->entityManager->flush();

        return [
            'success' => true,
            'data' => [
                'id' => $city->getId(),
                'name' => $city->getName(),
                'postalCode' => $city->getPostalCode()
            ]
        ];
    }
}
