<?php

namespace App\Service;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\MessageRead;
use App\Entity\User;
use App\Repository\MessageReadRepository;
use Doctrine\ORM\EntityManagerInterface;

class MessageReadService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MessageReadRepository $messageReadRepository
    ) {}

    /**
     * Marque tous les messages d'une conversation comme lus pour un utilisateur
     */
    public function markConversationAsRead(Conversation $conversation, User $user): void
    {
        $messages = $conversation->getMessages();

        foreach ($messages as $message) {
            // Ne pas marquer ses propres messages
            if ($message->getSender()->getId() === $user->getId()) {
                continue;
            }

            // Vérifier si déjà marqué comme lu
            $existingRead = $this->em->getRepository(MessageRead::class)
                ->findOneBy(['message' => $message, 'user' => $user]);

            if (!$existingRead) {
                $messageRead = new MessageRead();
                $messageRead->setMessage($message);
                $messageRead->setUser($user);
                $this->em->persist($messageRead);
            }
        }

        $this->em->flush();
    }

    /**
     * Compte les messages non lus d'une conversation pour un utilisateur
     */
    public function countUnreadMessages(Conversation $conversation, User $user): int
    {
        return $this->messageReadRepository->countUnreadMessages($conversation, $user);
    }

    /**
     * Compte le total de messages non lus pour un utilisateur
     */
    public function countAllUnreadMessages(User $user): int
    {
        return $this->messageReadRepository->countAllUnreadMessages($user);
    }

    /**
     * Vérifie si un message a été lu par un utilisateur
     */
    public function isMessageRead(Message $message, User $user): bool
    {
        $messageRead = $this->em->getRepository(MessageRead::class)
            ->findOneBy(['message' => $message, 'user' => $user]);

        return $messageRead !== null;
    }
}

