<?php

namespace App\Twig;

use App\Service\MessageReadService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MessageExtension extends AbstractExtension
{
    public function __construct(
        private MessageReadService $messageReadService,
        private Security $security
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('unread_messages_count', [$this, 'getUnreadMessagesCount']),
        ];
    }

    public function getUnreadMessagesCount(): int
    {
        $user = $this->security->getUser();

        if (!$user || !$user instanceof \App\Entity\User) {
            return 0;
        }

        return $this->messageReadService->countAllUnreadMessages($user);
    }
}
