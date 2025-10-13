<?php

namespace App\Command;

use App\Service\WeatherService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-weather',
    description: 'Test de l\'API météo OpenWeatherMap',
)]
class TestWeatherCommand extends Command
{
    public function __construct(private readonly WeatherService $weatherService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test de l\'API météo');

        try {
            $io->section('Test 1: Météo pour Rennes');
            $weather = $this->weatherService->getWeather('Rennes');

            if ($weather) {
                $io->success('Données météo récupérées avec succès !');
                $io->writeln('Réponse API:');
                $io->writeln(json_encode($weather, JSON_PRETTY_PRINT));

                if (isset($weather['main']['temp'])) {
                    $io->info('Température: ' . round($weather['main']['temp']) . '°C');
                }
                if (isset($weather['weather'][0]['description'])) {
                    $io->info('Description: ' . $weather['weather'][0]['description']);
                }
            } else {
                $io->error('Aucune donnée retournée');
            }

        } catch (\Exception $e) {
            $io->error('Erreur: ' . $e->getMessage());
            $io->writeln('Type d\'exception: ' . get_class($e));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

