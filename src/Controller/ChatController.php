<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\ParticipantGroup;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Repository\ParticipantGroupRepository;
use App\Service\MessageReadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chat')]
#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ConversationRepository $conversationRepository,
        private MessageRepository $messageRepository,
        private UserRepository $userRepository,
        private ParticipantGroupRepository $groupRepository,
        private MessageReadService $messageReadService
    ) {}

    #[Route('/', name: 'app_chat_index')]
    public function index(Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $conversations = $this->conversationRepository->findByUser($currentUser);

        // Calculer les messages non lus pour chaque conversation
        $unreadCounts = [];
        foreach ($conversations as $conversation) {
            $unreadCounts[$conversation->getId()] = $this->messageReadService->countUnreadMessages($conversation, $currentUser);
        }

        // Récupérer aussi les groupes de l'utilisateur
        $userGroups = $this->groupRepository->createQueryBuilder('g')
            ->leftJoin('g.members', 'm')
            ->where('g.owner = :user')
            ->orWhere('m.user = :user')
            ->setParameter('user', $currentUser)
            ->orderBy('g.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Si une conversation est spécifiée dans l'URL, la charger
        $conversationId = $request->query->get('conv');
        $selectedConversation = null;

        if ($conversationId) {
            $selectedConversation = $this->em->getRepository(Conversation::class)->find($conversationId);
            if ($selectedConversation && !$selectedConversation->isUserParticipant($currentUser)) {
                $selectedConversation = null;
            }
        } elseif (!empty($conversations)) {
            // Sélectionner automatiquement la première conversation
            $selectedConversation = $conversations[0];
        }

        return $this->render('chat/index.html.twig', [
            'conversations' => $conversations,
            'groups' => $userGroups,
            'conversation' => $selectedConversation,
            'unreadCounts' => $unreadCounts,
            'totalUnread' => $this->messageReadService->countAllUnreadMessages($currentUser),
        ]);
    }

    #[Route('/test/send/{id}', name: 'app_chat_test_send')]
    public function testSend(Conversation $conversation): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $message = new Message();
        $message->setContent('Message de test');
        $message->setSender($currentUser);
        $message->setConversation($conversation);

        $this->em->persist($message);
        $this->em->flush();

        $this->addFlash('success', 'Message de test créé avec ID: ' . $message->getId());
        return $this->redirectToRoute('app_chat_conversation', ['id' => $conversation->getId()]);
    }

    #[Route('/conversation/{id}/content', name: 'app_chat_conversation_content')]

    public function conversationContent(Conversation $conversation): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Vérifier que l'utilisateur fait partie de la conversation
        if (!$conversation->isUserParticipant($currentUser)) {
            return new Response('Accès refusé', 403);
        }

        // Marquer tous les messages comme lus
        $this->messageReadService->markConversationAsRead($conversation, $currentUser);

        return $this->render('chat/_conversation_content.html.twig', [
            'conversation' => $conversation,
        ]);
    }

    #[Route('/conversation/{id}/mark-read', name: 'app_chat_mark_read', methods: ['POST'])]
    public function markConversationAsRead(Conversation $conversation): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if (!$conversation->isUserParticipant($currentUser)) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $this->messageReadService->markConversationAsRead($conversation, $currentUser);

        return new JsonResponse([
            'success' => true,
            'unreadCount' => 0,
            'totalUnread' => $this->messageReadService->countAllUnreadMessages($currentUser)
        ]);
    }

    #[Route('/group/{id}/content', name: 'app_chat_group_content')]
    public function groupContent(ParticipantGroup $group): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Vérifier que l'utilisateur fait partie du groupe
        $isMember = false;
        foreach ($group->getMembers() as $member) {
            if ($member->getUser()->getId() === $currentUser->getId()) {
                $isMember = true;
                break;
            }
        }

        if (!$isMember && $group->getOwner()->getId() !== $currentUser->getId()) {
            return new Response('Accès refusé', 403);
        }

        // Chercher ou créer la conversation pour ce groupe
        $conversation = $this->em->getRepository(Conversation::class)
            ->findOneBy(['participantGroup' => $group, 'type' => 'group']);

        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->setType('group');
            $conversation->setName($group->getName());
            $conversation->setParticipantGroup($group);

            // Ajouter tous les membres du groupe
            $conversation->addParticipant($group->getOwner());
            foreach ($group->getMembers() as $member) {
                $conversation->addParticipant($member->getUser());
            }

            $this->em->persist($conversation);
            $this->em->flush();
        }

        return $this->render('chat/_conversation_content.html.twig', [
            'conversation' => $conversation,
        ]);
    }

    #[Route('/conversation/{id}', name: 'app_chat_conversation')]
    public function conversation(Conversation $conversation): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Vérifier que l'utilisateur fait partie de la conversation
        if (!$conversation->isUserParticipant($currentUser)) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette conversation.');
            return $this->redirectToRoute('app_chat_index');
        }

        // Rediriger vers l'index avec la conversation sélectionnée
        return $this->redirectToRoute('app_chat_index', ['conv' => $conversation->getId()]);
    }

    #[Route('/new/user/{id}', name: 'app_chat_new_user')]
    public function newUserConversation(User $user): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($user->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas créer une conversation avec vous-même.');
            return $this->redirectToRoute('app_chat_index');
        }

        $conversation = $this->conversationRepository->findOrCreatePrivateConversation($currentUser, $user);

        return $this->redirectToRoute('app_chat_conversation', ['id' => $conversation->getId()]);
    }

    #[Route('/new/group/{id}', name: 'app_chat_new_group')]
    public function newGroupConversation(ParticipantGroup $group): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Vérifier que l'utilisateur fait partie du groupe
        $isMember = false;
        foreach ($group->getMembers() as $member) {
            if ($member->getUser()->getId() === $currentUser->getId()) {
                $isMember = true;
                break;
            }
        }

        if (!$isMember && $group->getOwner()->getId() !== $currentUser->getId()) {
            $this->addFlash('error', 'Vous devez être membre du groupe pour accéder à cette conversation.');
            return $this->redirectToRoute('app_chat_index');
        }

        // Chercher une conversation existante pour ce groupe
        $conversation = $this->em->getRepository(Conversation::class)
            ->findOneBy(['participantGroup' => $group, 'type' => 'group']);

        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->setType('group');
            $conversation->setName($group->getName());
            $conversation->setParticipantGroup($group);

            // Ajouter tous les membres du groupe
            $conversation->addParticipant($group->getOwner());
            foreach ($group->getMembers() as $member) {
                $conversation->addParticipant($member->getUser());
            }

            $this->em->persist($conversation);
            $this->em->flush();
        }

        return $this->redirectToRoute('app_chat_conversation', ['id' => $conversation->getId()]);
    }

    #[Route('/message/send/{id}', name: 'app_chat_send_message', methods: ['POST'])]
    public function sendMessage(Conversation $conversation, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if (!$conversation->isUserParticipant($currentUser)) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $content = $request->request->get('content');

        if (empty(trim($content))) {
            return new JsonResponse(['error' => 'Le message ne peut pas être vide'], 400);
        }

        try {
            $message = new Message();
            $message->setContent($content);
            $message->setSender($currentUser);
            $message->setConversation($conversation);

            $this->em->persist($message);
            $this->em->flush();

            // Log pour déboguer
            error_log("Message créé avec ID: " . $message->getId());
            error_log("Content: " . $message->getContent());
            error_log("Sender: " . $message->getSender()->getPseudo());

            return new JsonResponse([
                'success' => true,
                'message' => [
                    'id' => $message->getId(),
                    'content' => $message->getContent(),
                    'sender' => [
                        'id' => $currentUser->getId(),
                        'pseudo' => $currentUser->getPseudo(),
                        'photo' => $currentUser->getPhotoFilename(),
                    ],
                    'createdAt' => $message->getCreatedAt()?->format('d/m/Y H:i') ?? date('d/m/Y H:i'),
                    'isEdited' => $message->isEdited(),
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Erreur lors de l'envoi du message: " . $e->getMessage());
            return new JsonResponse(['error' => 'Erreur lors de l\'envoi: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/message/edit/{id}', name: 'app_chat_edit_message', methods: ['POST'])]
    public function editMessage(Message $message, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($message->getSender()->getId() !== $currentUser->getId()) {
            return new JsonResponse(['error' => 'Vous ne pouvez modifier que vos propres messages'], 403);
        }

        $content = $request->request->get('content');

        if (empty(trim($content))) {
            return new JsonResponse(['error' => 'Le message ne peut pas être vide'], 400);
        }

        $message->setContent($content);
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'isEdited' => $message->isEdited(),
                'updatedAt' => $message->getUpdatedAt()->format('d/m/Y H:i'),
            ]
        ]);
    }

    #[Route('/message/delete/{id}', name: 'app_chat_delete_message', methods: ['POST'])]
    public function deleteMessage(Message $message): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($message->getSender()->getId() !== $currentUser->getId()) {
            return new JsonResponse(['error' => 'Vous ne pouvez supprimer que vos propres messages'], 403);
        }

        $this->em->remove($message);
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/messages/load/{id}', name: 'app_chat_load_messages', methods: ['GET'])]
    public function loadMessages(Conversation $conversation, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if (!$conversation->isUserParticipant($currentUser)) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $offset = $request->query->getInt('offset', 0);
        $limit = $request->query->getInt('limit', 50);

        $messages = $this->messageRepository->findByConversation($conversation, $limit, $offset);

        $messagesData = array_map(function(Message $message) use ($currentUser) {
            return [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'sender' => [
                    'id' => $message->getSender()->getId(),
                    'pseudo' => $message->getSender()->getPseudo(),
                    'photo' => $message->getSender()->getPhotoFilename(),
                ],
                'createdAt' => $message->getCreatedAt()?->format('d/m/Y H:i') ?? date('d/m/Y H:i'),
                'isEdited' => $message->isEdited(),
                'isOwn' => $message->getSender()->getId() === $currentUser->getId(),
            ];
        }, array_reverse($messages));

        return new JsonResponse([
            'messages' => $messagesData,
            'hasMore' => count($messages) === $limit,
        ]);
    }

    #[Route('/users/search', name: 'app_chat_search_users', methods: ['GET'])]
    public function searchUsers(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return new JsonResponse(['users' => []]);
        }

        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.pseudo LIKE :query OR u.firstName LIKE :query OR u.lastName LIKE :query')
            ->andWhere('u.active = true')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $usersData = array_map(function(User $user) {
            return [
                'id' => $user->getId(),
                'pseudo' => $user->getPseudo(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'photo' => $user->getPhotoFilename(),
            ];
        }, $users);

        return new JsonResponse(['users' => $usersData]);
    }
}
