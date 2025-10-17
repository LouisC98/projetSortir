<?php

namespace App\Command;

use App\Message\SendSortieReminderEmailMessage;
use App\Repository\SortieRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:send-reminders',
    description: 'Trouve les sorties qui commence dans 48 et 24h pour envoyer un rappel aux participants',
)]
class SendReminderCommand extends Command
{
    public function __construct(
        private readonly SortieRepository $sortieRepository,
        private readonly MessageBusInterface $bus
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        
        $from48h = (clone $now)->modify('+47 hours');
        $to48h = (clone $now)->modify('+48 hours');
        $sorties48h = $this->sortieRepository->findForReminders($from48h, $to48h);

        $from24h = (clone $now)->modify('+23 hours');
        $to24h = (clone $now)->modify('+24 hours');
        $sorties24h = $this->sortieRepository->findForReminders($from24h, $to24h);
        
        $allSorties = array_merge($sorties48h, $sorties24h);

        if (empty($allSorties)) {
            $io->info('Pas de sortie trouvé pour rappels');
            return Command::SUCCESS;
        }

        $io->info(count($allSorties) . ' sorties trouvée(s) pour rappels');

        foreach ($allSorties as $sortie) {
            $this->bus->dispatch(new SendSortieReminderEmailMessage($sortie->getId()));
            $io->text('Dispatch rappel pour sortie #' . $sortie->getId() . ' - ' . $sortie->getName());
        }

        $io->success('Tout les rappels ont été dispatch');

        return Command::SUCCESS;
    }
}
