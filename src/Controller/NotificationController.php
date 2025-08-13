<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api')]
class NotificationController extends AbstractController
{

    // #[Route('/notifications/{docteurId}', name: 'api_notifications_patient', methods: ['GET'])]
    // public function getNotifications(int $docteurId, NotificationRepository $notificationRepository, SerializerInterface $serializer): JsonResponse
    // {
    //     $notifications = $notificationRepository->findBy(['docteur' => $docteurId, 'statut' => false]);

    //     $jsonData = $serializer->serialize($notifications, 'json', ['groups' => 'notification:read']);

    //     return new JsonResponse($jsonData, 200, [], true);
    // }

    #[Route('/mes-notifications', name: 'mes_notifications', methods: ['GET'])]
    public function mesNotifications(Security $security, NotificationRepository $notificationRepository): JsonResponse
    {
        $user = $security->getUser();

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non connecté'], 401);
        }

        // Vérifie si l'utilisateur est patient ou docteur
        /** @var \App\Entity\User $user */
        $user = $security->getUser();
        $patient = $user->getPatient();
        $docteur = $user->getDocteur();

        if (!$patient && !$docteur) {
            return $this->json(['error' => 'Rôle inconnu ou non géré'], 403);
        }

        // On récupère les notifications en fonction du rôle
        $notifications = $notificationRepository->findBy(
            $patient ? ['patient' => $patient] : ['docteur' => $docteur],
            ['dateHeureAt' => 'DESC']
        );

        $data = [];

        foreach ($notifications as $notification) {
            $data[] = [
                'id' => $notification->getId(),
                'message' => $notification->getMessage(),
                'type' => $notification->getType(),
                'statut' => $notification->getStatut(),
                'dateHeureAt' => $notification->getDateHeureAt()->format('Y-m-d H:i:s'),
                'patient_id' => $notification->getPatient()?->getId(),
                'docteur_nom' => $notification->getDocteur()
                    ? $notification->getDocteur()->getNom() . ' ' . $notification->getDocteur()->getPrenom()
                    : null,
            ];
        }

        return $this->json($data);
    }


}
