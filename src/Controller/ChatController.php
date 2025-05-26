<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageType;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/chat')]
class ChatController extends AbstractController
{
    #[Route('/', name: 'app_chat_index')]
    public function index(UserRepository $userRepository, MessageRepository $messageRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $currentUser = $this->getUser();
        $users = $userRepository->findUsersExcept($currentUser);
        $unreadCount = $messageRepository->countUnreadMessages($currentUser);
        
        return $this->render('chat/index.html.twig', [
            'users' => $users,
            'current_user' => $currentUser,
            'unread_count' => $unreadCount
        ]);
    }

    #[Route('/conversation/{id}', name: 'app_chat_conversation')]
    public function conversation(User $user, MessageRepository $messageRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $currentUser = $this->getUser();
        
        if ($currentUser === $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas discuter avec vous-même');
        }
        
        $messages = $messageRepository->findConversation($currentUser, $user);
        
        // Marquer les messages comme lus
        $messageRepository->markAsRead($user, $currentUser);
        
        $form = $this->createForm(MessageType::class);
        
        return $this->render('chat/conversation.html.twig', [
            'messages' => $messages,
            'destinataire' => $user,
            'current_user' => $currentUser,
            'form' => $form->createView()
        ]);
    }

    #[Route('/api/send/{id}', name: 'api_chat_send', methods: ['POST'])]
    public function sendMessage(Request $request, User $destinataire, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $currentUser = $this->getUser();
        
        if ($currentUser === $destinataire) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas vous envoyer un message'], 400);
        }
        
        $data = json_decode($request->getContent(), true);
        $contenu = trim($data['contenu'] ?? '');
        
        if (empty($contenu)) {
            return new JsonResponse(['error' => 'Le message ne peut pas être vide'], 400);
        }
        
        $message = new Message();
        $message->setExpediteur($currentUser);
        $message->setDestinataire($destinataire);
        $message->setContenu($contenu);
        
        $entityManager->persist($message);
        $entityManager->flush();
        
        return new JsonResponse([
            'id' => $message->getId(),
            'contenu' => $message->getContenu(),
            'expediteur' => [
                'id' => $currentUser->getId(),
                'email' => $currentUser->getEmail()
            ],
            'createdAt' => $message->getCreatedAt()->format('c'), // Format ISO 8601
            'isRead' => $message->isRead()
        ]);
    }


    #[Route('/api/messages/{id}', name: 'api_chat_messages', methods: ['GET'])]
    public function getMessages(User $user, Request $request, MessageRepository $messageRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $currentUser = $this->getUser();
        $since = $request->query->get('since');
        
        if ($since) {
            try {
                // Essayer de parser la date au format ISO
                $sinceDate = new \DateTimeImmutable($since);
                $messages = $messageRepository->findNewMessages($currentUser, $user, $sinceDate);
            } catch (\Exception $e) {
                // Si le parsing échoue, retourner une erreur
                return new JsonResponse(['error' => 'Format de date invalide'], 400);
            }
        } else {
            $messages = $messageRepository->findConversation($currentUser, $user);
        }
        
        $formattedMessages = [];
        foreach ($messages as $message) {
            $formattedMessages[] = [
                'id' => $message->getId(),
                'contenu' => $message->getContenu(),
                'expediteur' => [
                    'id' => $message->getExpediteur()->getId(),
                    'email' => $message->getExpediteur()->getEmail()
                ],
                'destinataire' => [
                    'id' => $message->getDestinataire()->getId(),
                    'email' => $message->getDestinataire()->getEmail()
                ],
                'createdAt' => $message->getCreatedAt()->format('c'), // Format ISO 8601
                'isRead' => $message->isRead()
            ];
        }
        
        // Marquer les nouveaux messages comme lus
        if ($since && !empty($messages)) {
            $messageRepository->markAsRead($user, $currentUser);
        }
        
        return new JsonResponse($formattedMessages);
    }


    #[Route('/api/unread-count', name: 'api_chat_unread_count', methods: ['GET'])]
    public function getUnreadCount(MessageRepository $messageRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $currentUser = $this->getUser();
        $unreadCount = $messageRepository->countUnreadMessages($currentUser);
        
        return new JsonResponse(['count' => $unreadCount]);
    }
}
