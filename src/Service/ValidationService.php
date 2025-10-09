<?php

namespace App\Service;

class ValidationService
{
    public function validateCity(string $name, string $postalCode): ?string
    {
        if (strlen($name) < 2) {
            return 'Le nom de la ville doit contenir au moins 2 caractères';
        }

        if (strlen($name) > 100) {
            return 'Le nom de la ville ne peut pas dépasser 100 caractères';
        }

        if (!preg_match('/^[a-zA-ZÀ-ÿ\s\-\']+$/u', $name)) {
            return 'Le nom de la ville ne peut contenir que des lettres, espaces, traits d\'union et apostrophes';
        }

        if (!preg_match('/^\d{5}$/', $postalCode)) {
            return 'Le code postal doit contenir exactement 5 chiffres';
        }

        return null;
    }

    public function validatePlace(string $name, string $street, ?string $latitude, ?string $longitude): ?string
    {
        if (strlen($name) < 2) {
            return 'Le nom du lieu doit contenir au moins 2 caractères';
        }

        if (strlen($name) > 255) {
            return 'Le nom du lieu ne peut pas dépasser 255 caractères';
        }

        if (strlen($street) < 5) {
            return 'L\'adresse doit contenir au moins 5 caractères';
        }

        if (strlen($street) > 255) {
            return 'L\'adresse ne peut pas dépasser 255 caractères';
        }

        if ($latitude !== null && $latitude !== '') {
            if (!is_numeric($latitude)) {
                return 'La latitude doit être un nombre valide';
            }
            $lat = (float) $latitude;
            if ($lat < -90 || $lat > 90) {
                return 'La latitude doit être entre -90 et 90';
            }
        }

        if ($longitude !== null && $longitude !== '') {
            if (!is_numeric($longitude)) {
                return 'La longitude doit être un nombre valide';
            }
            $lon = (float) $longitude;
            if ($lon < -180 || $lon > 180) {
                return 'La longitude doit être entre -180 et 180';
            }
        }

        return null;
    }
}
