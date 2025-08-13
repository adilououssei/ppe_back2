<?php

namespace App\Controller;

use App\Entity\Docteur;
use App\Entity\Patient;
use App\Entity\RendezVous;
use App\Entity\Notification;
use App\Service\NotificationService;
use App\Repository\DocteurRepository;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class RendezVousController extends AbstractController
{
    private $notificationService;
    private $rendezVousRepository;

    public function __construct(NotificationService $notificationService, RendezVousRepository $rendezVousRepository)
    {
        $this->notificationService = $notificationService;
        $this->rendezVousRepository = $rendezVousRepository;
    }
    #[Route('/api/rendezVous', name: "app_create_rendezVous", methods: ['POST'])]
    public function createRendezVous(
        Request $request,
        EntityManagerInterface $em,
        DocteurRepository $docteurRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        // 1. Vérification de l'authentification
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return new JsonResponse(
                ['error' => "Utilisateur non authentifié"],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // 2. Vérification du profil patient
        $patient = $user->getPatient();
        if (!$patient) {
            return new JsonResponse(
                ['error' => "Aucun profil patient associé à ce compte"],
                Response::HTTP_FORBIDDEN
            );
        }

        // 3. Récupération et validation des données
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(
                ['error' => 'Données JSON invalides'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // 4. Validation des champs obligatoires
        $requiredFields = [
            'dateRendezVous' => 'Date du rendez-vous manquante',
            'heureRendezVous' => 'Heure du rendez-vous manquante',
            'docteur' => 'Docteur non spécifié'
        ];

        foreach ($requiredFields as $field => $errorMessage) {
            if (!isset($data[$field])) {
                return new JsonResponse(
                    ['error' => $errorMessage],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        // 5. Validation du format de date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['dateRendezVous'])) {
            return new JsonResponse(
                ['error' => 'Format de date invalide. Utilisez YYYY-MM-DD'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // 6. Validation du format d'heure
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['heureRendezVous'])) {
            return new JsonResponse(
                ['error' => "Format d'heure invalide. Utilisez HH:MM"],
                Response::HTTP_BAD_REQUEST
            );
        }

        // 7. Recherche du docteur
        $docteur = $docteurRepository->find((int)$data['docteur']);
        if (!$docteur) {
            return new JsonResponse(
                ['error' => 'Docteur introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        try {
            // 8. Création de l'objet DateTime
            $dateTime = \DateTime::createFromFormat(
                'Y-m-d H:i',
                $data['dateRendezVous'] . ' ' . $data['heureRendezVous']
            );

            if (!$dateTime) {
                throw new \Exception('La combinaison date/heure est invalide');
            }

            // 9. Création du rendez-vous
            $rendezVous = new RendezVous();
            $rendezVous->setDateConsultationAt(\DateTimeImmutable::createFromMutable($dateTime));
            $rendezVous->setHeureConsultation(\DateTimeImmutable::createFromMutable($dateTime));
            $rendezVous->setDescription($data['descriptionRendezVous'] ?? "Pas de description");
            $rendezVous->setTypeConsultation($data['typeConsultation'] ?? "en_cabinet");
            $rendezVous->setStatut("en_attente");
            $rendezVous->setDocteur($docteur);
            $rendezVous->setPatient($patient);

            // 10. Validation de l'entité
            $errors = $validator->validate($rendezVous);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse(
                    ['error' => $errorMessages],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // 11. Persistance en base de données
            $em->persist($rendezVous);
            $em->flush();

            // 12. Notification au patient
            $this->createNotification(
                $em,
                $patient,
                "Votre demande de rendez-vous avec le Dr. " . $docteur->getNom() . " a été envoyée.",
                "demande_rendezVous"
            );

            // 13. Notification au docteur
            $this->createNotification(
                $em,
                null,
                "Nouvelle demande de rendez-vous de " . $patient->getUser()->getEmail(),
                "demande_rendezVous",
                $docteur
            );

            // 14. Retour de la réponse
            return new JsonResponse(
                [
                    'message' => 'Rendez-vous créé avec succès',
                    'id' => $rendezVous->getId()
                ],
                Response::HTTP_CREATED
            );

        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Erreur lors de la création: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Crée une notification
     */
    private function createNotification(
        EntityManagerInterface $em,
        $patient = null,
        string $message,
        string $type,
        $docteur = null
    ): void {
        $notification = new Notification();
        $notification->setMessage($message);
        $notification->setDateHeureAt(new \DateTimeImmutable());
        $notification->setType($type);
        $notification->setStatut(false);

        if ($patient) {
            $notification->setPatient($patient);
        }

        if ($docteur) {
            $notification->setDocteur($docteur);
        }

        $em->persist($notification);
        $em->flush();
    }

    #[Route('/api/rendezVous/{id}', name: "app_rendezvous_show", methods: ['GET'])]
    public function showRendezVous(RendezVous $rendezVous, SerializerInterface $serializer): JsonResponse
    {
        $jsonRendezVous = $serializer->serialize($rendezVous, 'json', ['group' => 'getRendezVous']);

        return new JsonResponse($jsonRendezVous, Response::HTTP_OK, [], true);
    }

    //    #[Route('/api/rendezVous', name:"app_rendezvous_liste", methods: ['GET'])]
    //    public function indexRendezVous(RendezVousRepository $rendezVousRepository, SerializerInterface $serializer): JsonResponse
    //    {
    //        $rendezVous = $rendezVousRepository->findAll();
    //        $jsonRendezVous = $serializer->serialize($rendezVous, 'json', ['groups' => 'getRendezVous']);

    //        return new JsonResponse($jsonRendezVous, Response::HTTP_OK, [], true);
    //    }

    #[Route('/api/mes-rendezvous', name: 'mes_rendezvous', methods: ['GET'])]
    public function mesRendezVous(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        // On récupère le Patient lié à l'utilisateur
        $patient = $em->getRepository(Patient::class)->findOneBy(['user' => $user]);

        if (!$patient) {
            return $this->json(['error' => 'Aucun patient lié à cet utilisateur.'], 404);
        }

        // Récupère les rendez-vous du patient
        $rendezVous = $em->getRepository(RendezVous::class)->findBy(['patient' => $patient]);

        return $this->json($rendezVous, 200, [], ['groups' => 'getRendezVous']);
    }

    #[Route('/api/mes-rendezvous-docteur', name: 'mes_rendezvous_docteur', methods: ['GET'])]
    public function mesRendezVousDocteur(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $docteur = $em->getRepository(Docteur::class)->findOneBy(['user' => $user]);

        if (!$docteur) {
            return $this->json(['error' => 'Aucun docteur lié à cet utilisateur.'], 404);
        }

        $rendezVous = $em->getRepository(RendezVous::class)->findBy(['docteur' => $docteur]);

        return $this->json($rendezVous, 200, [], ['groups' => 'getRendezVous']);
    }

    #[Route('/api/rendezvous/{id}/update', name: 'update_rendezvous', methods: ['PUT'])]
    public function updateRendezVous(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $rdv = $em->getRepository(RendezVous::class)->find($id);

        if (!$rdv) {
            return $this->json(['error' => 'Rendez-vous non trouvé.'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['statut'])) {
            $rdv->setStatut($data['statut']);
        }

        try {
            if (isset($data['dateConsultationAt'])) {
                try {
                    $rdv->setDateConsultationAt(new \DateTimeImmutable($data['dateConsultationAt']));
                } catch (\Exception $e) {
                    return $this->json(['error' => 'Format de date invalide.'], 400);
                }
            }

            if (isset($data['heureConsultation'])) {
                try {
                    $time = \DateTimeImmutable::createFromFormat('H:i', $data['heureConsultation']);
                    if (!$time) {
                        throw new \RuntimeException("Format d'heure invalide");
                    }
                    $rdv->setHeureConsultation($time);
                } catch (\Exception $e) {
                    return $this->json(['error' => 'Format d\'heure invalide.'], 400);
                }
            }

            $patient = $rdv->getPatient();
            $message = '';

            if ($data['statut'] === 'accepté') {
                $date = $rdv->getDateConsultationAt()?->format('Y-m-d');
                $heure = $rdv->getHeureConsultation()?->format('H:i');
                $message = "Votre rendez-vous a été accepté pour le {$date} à {$heure}.";
            } elseif ($data['statut'] === 'refusé') {
                $message = "Votre rendez-vous a été refusé.";
            }

            if ($message && $patient) {
                $notification = new Notification();
                $notification->setPatient($patient);
                $notification->setMessage($message);
                $notification->setDateHeureAt(new \DateTimeImmutable());
                $em->persist($notification);
            }

            $em->flush();

            $em->flush();

            return $this->json($rdv, 200, [], ['groups' => 'getRendezVous']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la mise à jour',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
