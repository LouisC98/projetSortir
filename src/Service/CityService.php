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

    /**
     * Crée une nouvelle ville après validation des données
     *
     * Vérifie que la ville n'existe pas déjà (nom + code postal) et valide
     * les contraintes métier avant la création.
     *
     * @param string $name Le nom de la ville
     * @param string $postalCode Le code postal de la ville (5 chiffres)
     *
     * @return array{success: bool, message?: string, data?: array{id: int, name: string, postalCode: string}}
     *               Tableau contenant le résultat de l'opération avec les données de la ville créée en cas de succès
     */
    public function createCity(string $name, string $postalCode): array
    {
        $name = trim($name);
        $postalCode = trim($postalCode);

        $error = $this->validationService->validateCity($name, $postalCode);
        if ($error) {
            return ['success' => false, 'message' => $error];
        }

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
