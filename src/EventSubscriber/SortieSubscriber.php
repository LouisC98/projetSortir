<?php

namespace App\EventSubscriber;

use App\Event\SortieRegistrationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class SortieSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SortieRegistrationEvent::NAME => 'onSortieRegistration',
        ];
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function onSortieRegistration(SortieRegistrationEvent $event): void
    {
        $sortie = $event->getSortie();
        $user = $event->getUser();
        $action = $event->getAction();

        if ($action === 'inscription') {
            $subject = 'Confirmation d\'inscription à la sortie : ' . $sortie->getName();
            $template = 'emails/inscription.html.twig';
        } else {
            $subject = 'Confirmation de désistement de la sortie : ' . $sortie->getName();
            $template = 'emails/desistement.html.twig';
        }

        $email = (new TemplatedEmail())
            ->from('no-reply@sortir.com')
            ->to($user->getEmail())
            ->subject($subject)
            ->htmlTemplate($template)
            ->context([
                'sortie' => $sortie,
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }
}
