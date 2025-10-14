<?php

namespace App\Command;

use App\Service\GeoApiService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-geo-api',
    description: 'Test de l\'API geo.api.gouv.fr',
)]
class TestGeoApiCommand extends Command
{
    public function __construct(private readonly GeoApiService $geoApiService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('city', InputArgument::OPTIONAL, 'Nom de la ville à rechercher', 'Rennes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cityName = $input->getArgument('city');

        $io->title('Test de l\'API geo.api.gouv.fr');

        try {
            // Test 1: Recherche de villes
            $io->section("Test 1: Recherche de villes avec '{$cityName}'");
            $cities = $this->geoApiService->searchCities($cityName, 5);

            if (empty($cities)) {
                $io->warning('Aucune ville trouvée');
            } else {
                $io->success(count($cities) . ' ville(s) trouvée(s)');

                $formatted = $this->geoApiService->formatCitiesForAutocomplete($cities);

                $rows = [];
                foreach ($formatted as $city) {
                    $rows[] = [
                        $city['name'],
                        $city['postalCode'],
                        number_format($city['population']) . ' hab.',
                        $city['latitude'] ? round($city['latitude'], 4) : 'N/A',
                        $city['longitude'] ? round($city['longitude'], 4) : 'N/A',
                    ];
                }

                $io->table(
                    ['Nom', 'Code Postal', 'Population', 'Latitude', 'Longitude'],
                    $rows
                );
            }

            // Test 2: Récupération des coordonnées
            if (!empty($cities)) {
                $firstCity = $cities[0];
                $io->section("Test 2: Récupération des coordonnées pour " . $firstCity['nom']);

                $postalCode = $firstCity['codesPostaux'][0] ?? null;
                $coordinates = $this->geoApiService->getCityCoordinates($firstCity['nom'], $postalCode);

                if ($coordinates) {
                    $io->success('Coordonnées récupérées avec succès !');
                    $io->writeln('Latitude: ' . $coordinates['latitude']);
                    $io->writeln('Longitude: ' . $coordinates['longitude']);
                } else {
                    $io->error('Impossible de récupérer les coordonnées');
                }
            }

            // Test 3: Recherche par code postal
            $io->section('Test 3: Recherche par code postal (35000)');
            $cityByPostalCode = $this->geoApiService->getCityByPostalCode('35000');

            if ($cityByPostalCode) {
                $io->success('Ville trouvée: ' . $cityByPostalCode['nom']);
                $io->writeln('Code: ' . $cityByPostalCode['code']);
                $io->writeln('Codes postaux: ' . implode(', ', $cityByPostalCode['codesPostaux']));
                if (isset($cityByPostalCode['centre'])) {
                    $io->writeln('Latitude: ' . $cityByPostalCode['centre']['coordinates'][1]);
                    $io->writeln('Longitude: ' . $cityByPostalCode['centre']['coordinates'][0]);
                }
            } else {
                $io->error('Ville non trouvée');
            }

        } catch (\Exception $e) {
            $io->error('Erreur: ' . $e->getMessage());
            $io->writeln('Type d\'exception: ' . get_class($e));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

