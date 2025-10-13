<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GeoApiService
{
    private const API_URL = 'https://geo.api.gouv.fr';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Recherche des villes par nom (autocomplétion)
     *
     * @param string $query Le nom de la ville à rechercher
     * @param int $limit Nombre de résultats maximum
     * @return array Liste des villes trouvées
     */
    public function searchCities(string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', self::API_URL . '/communes', [
                'query' => [
                    'nom' => $query,
                    'fields' => 'nom,code,codesPostaux,centre,population',
                    'boost' => 'population',
                    'limit' => $limit
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Erreur API geo.api.gouv.fr', [
                    'status' => $response->getStatusCode()
                ]);
                return [];
            }

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la recherche de villes', [
                'message' => $e->getMessage(),
                'query' => $query
            ]);
            return [];
        }
    }

    /**
     * Récupère les informations détaillées d'une ville par son code postal
     *
     * @param string $postalCode Le code postal
     * @return array|null Les informations de la ville
     */
    public function getCityByPostalCode(string $postalCode): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_URL . '/communes', [
                'query' => [
                    'codePostal' => $postalCode,
                    'fields' => 'nom,code,codesPostaux,centre,population',
                    'limit' => 1
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray();
            return $data[0] ?? null;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération de la ville', [
                'message' => $e->getMessage(),
                'postalCode' => $postalCode
            ]);
            return null;
        }
    }

    /**
     * Récupère les coordonnées GPS d'une ville
     *
     * @param string $cityName Nom de la ville
     * @param string|null $postalCode Code postal (optionnel pour affiner)
     * @return array|null ['latitude' => float, 'longitude' => float]
     */
    public function getCityCoordinates(string $cityName, ?string $postalCode = null): ?array
    {
        try {
            $queryParams = [
                'nom' => $cityName,
                'fields' => 'nom,codesPostaux,centre',
                'limit' => 1
            ];

            if ($postalCode) {
                $queryParams['codePostal'] = $postalCode;
            }

            $response = $this->httpClient->request('GET', self::API_URL . '/communes', [
                'query' => $queryParams
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray();
            if (empty($data) || !isset($data[0]['centre'])) {
                return null;
            }

            return [
                'latitude' => $data[0]['centre']['coordinates'][1],
                'longitude' => $data[0]['centre']['coordinates'][0]
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des coordonnées', [
                'message' => $e->getMessage(),
                'cityName' => $cityName,
                'postalCode' => $postalCode
            ]);
            return null;
        }
    }

    /**
     * Formatte les résultats pour l'autocomplétion
     *
     * @param array $cities Résultats de l'API
     * @return array Résultats formatés
     */
    public function formatCitiesForAutocomplete(array $cities): array
    {
        return array_map(function ($city) {
            $postalCodes = implode(', ', $city['codesPostaux'] ?? []);
            return [
                'id' => $city['code'],
                'name' => $city['nom'],
                'postalCodes' => $city['codesPostaux'] ?? [],
                'postalCode' => $city['codesPostaux'][0] ?? '',
                'latitude' => $city['centre']['coordinates'][1] ?? null,
                'longitude' => $city['centre']['coordinates'][0] ?? null,
                'population' => $city['population'] ?? 0,
                'label' => $city['nom'] . ' (' . $postalCodes . ')'
            ];
        }, $cities);
    }

    /**
     * Recherche des adresses pour l'autocomplétion des lieux
     *
     * @param string $query La recherche (adresse ou lieu)
     * @param string|null $cityName Nom de la ville pour filtrer
     * @param int $limit Nombre de résultats maximum
     * @return array Liste des adresses trouvées
     */
    public function searchAddresses(string $query, ?string $cityName = null, int $limit = 10): array
    {
        if (strlen($query) < 3) {
            return [];
        }

        try {
            $queryParams = [
                'q' => $query,
                'limit' => $limit,
                'autocomplete' => 1
            ];

            if ($cityName) {
                $queryParams['citycode'] = $cityName;
            }

            $response = $this->httpClient->request('GET', 'https://api-adresse.data.gouv.fr/search/', [
                'query' => $queryParams
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Erreur API adresse.data.gouv.fr', [
                    'status' => $response->getStatusCode()
                ]);
                return [];
            }

            $data = $response->toArray();
            return $data['features'] ?? [];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la recherche d\'adresses', [
                'message' => $e->getMessage(),
                'query' => $query
            ]);
            return [];
        }
    }

    /**
     * Formatte les résultats d'adresses pour l'autocomplétion
     *
     * @param array $addresses Résultats de l'API
     * @return array Résultats formatés
     */
    public function formatAddressesForAutocomplete(array $addresses): array
    {
        return array_map(function ($address) {
            $properties = $address['properties'] ?? [];
            $coordinates = $address['geometry']['coordinates'] ?? [null, null];

            return [
                'label' => $properties['label'] ?? '',
                'name' => $properties['name'] ?? '',
                'street' => $properties['street'] ?? $properties['name'] ?? '',
                'city' => $properties['city'] ?? '',
                'postcode' => $properties['postcode'] ?? '',
                'latitude' => $coordinates[1] ?? null,
                'longitude' => $coordinates[0] ?? null,
                'type' => $properties['type'] ?? 'street'
            ];
        }, $addresses);
    }
}
