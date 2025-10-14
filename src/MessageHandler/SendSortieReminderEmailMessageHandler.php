<?php

namespace App\MessageHandler;

use App\Entity\User;
use App\Message\SendSortieReminderEmailMessage;
use App\Repository\SortieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendSortieReminderEmailMessageHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly SortieRepository $sortieRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(SendSortieReminderEmailMessage $message): void
    {
        $sortie = $this->sortieRepository->find($message->getSortieId());

        if (!$sortie) {
            $this->logger->error(sprintf('Sortie with id \'%d\' not found for reminder email.', $message->getSortieId()));
            return;
        }

        $recipients = new ArrayCollection();

        foreach ($sortie->getParticipants() as $participant) {
            if (!$recipients->contains($participant)) {
                $recipients->add($participant);
            }
        }

        $organizer = $sortie->getOrganisateur();
        if ($organizer && !$recipients->contains($organizer)) {
            $recipients->add($organizer);
        }

        if ($recipients->isEmpty()) {
            $this->logger->info(sprintf('Sortie #%d has no participants for reminder.', $sortie->getId()));
            return;
        }

        $now = new \DateTime();
        $interval = $now->diff($sortie->getStartDateTime());
        $hoursRemaining = ($interval->days * 24) + $interval->h;

        if ($hoursRemaining >= 47) {
            $reminderType = '48 heures';
        } else {
            $reminderType = '24 heures';
        }

        foreach ($recipients as $recipient) {
            if (!$recipient instanceof User || !$recipient->getEmail()) {
                continue;
            }

            $email = (new TemplatedEmail())
                ->from('no-reply@sortir.com')
                ->to($recipient->getEmail())
                ->subject(sprintf('Rappel : Votre sortie \'%s\' est dans moins de %s !', $sortie->getName(), $reminderType))
                ->htmlTemplate('emails/sortie_reminder.html.twig')
                ->context([
                    'sortie' => $sortie,
                    'user' => $recipient,
                    'reminderType' => $reminderType
                ]);

            try {
                $this->mailer->send($email);
            } catch (\Exception $e) {
                $this->logger->error(sprintf('Failed to send reminder email to %s for sortie #%d: %s', $recipient->getEmail(), $sortie->getId(), $e->getMessage()));
            }
        }
    }
}
