<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Docteur;
use App\Entity\RendezVous;
use App\Entity\Consultation;
use App\Repository\DocteurRepository;
use App\Repository\SpecialiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class DocteurController extends AbstractController
{
    #[Route('/api/docteurs', name: 'app_docteur', methods: ['GET'])]
    public function ListeDocteur(DocteurRepository $docteurRepository, SerializerInterface $serializer, Request $request): JsonResponse
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = max(1, (int)$request->query->get('limit', 10));

        $query = $docteurRepository->createQueryBuilder('d')
            ->orderBy('d.nom', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery();

        $paginator = new Paginator($query, true);
        $total = count($paginator);

        $docteurs = iterator_to_array($paginator);

        $json = $serializer->serialize($docteurs, 'json', ['groups' => 'getDocteur']);

        return new JsonResponse([
            'data' => json_decode($json),
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => ceil($total / $limit)
        ]);
    }

    #[Route('/api/docteurs/{id}', name: 'app_docteur_show', methods: ['GET'])]
    public function show(Docteur $docteur, SerializerInterface $serializer): JsonResponse
    {
        $jsonDocteur = $serializer->serialize($docteur, 'json', ['groups' => 'getDocteur']);
        return new JsonResponse($jsonDocteur, Response::HTTP_OK, [], true);
    }

    #[Route('/api/docteurs', name: 'app_docteur_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        SpecialiteRepository $specialiteRepo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Vérification des champs obligatoires
        if (empty($data['email']) || empty($data['password']) || empty($data['nom']) || empty($data['prenom']) || empty($data['telephone'])) {
            return new JsonResponse(['error' => 'Tous les champs obligatoires doivent être remplis'], Response::HTTP_BAD_REQUEST);
        }

        // Création User
        $user = new User();
        $user->setEmail($data['email']);
        $user->setRoles(['ROLE_DOCTEUR']); // rôle correct
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Création Docteur
        $docteur = new Docteur();
        $docteur->setNom($data['nom']);
        $docteur->setPrenom($data['prenom']);
        $docteur->setTelephone($data['telephone']);
        $docteur->setUser($user);

        // Association spécialité (si fournie)
        if (!empty($data['specialiteId'])) {
            $specialite = $specialiteRepo->find($data['specialiteId']);
            if ($specialite) {
                $docteur->addSpecialite($specialite); // méthode standard pour ManyToMany
            }
        }

        try {
            $em->persist($user);
            $em->persist($docteur);
            $em->flush();

            return new JsonResponse([
                'message' => 'Médecin créé avec succès',
                'docteurId' => $docteur->getId()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la création du médecin',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route('/api/docteurs/{id}', name: 'app_docteur_update', methods: ['PUT'])]
    public function update(
        Request $request,
        Docteur $docteur,
        EntityManagerInterface $em,
        SpecialiteRepository $specialiteRepo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $docteur->setNom($data['nom']);
        }
        if (isset($data['prenom'])) {
            $docteur->setPrenom($data['prenom']);
        }
        if (isset($data['telephone'])) {
            $docteur->setTelephone($data['telephone']);
        }
        if (isset($data['email'])) {
            $docteur->getUser()->setEmail($data['email']);
        }

        // Mise à jour des spécialités
        if (!empty($data['specialiteId'])) {
            $specialite = $specialiteRepo->find($data['specialiteId']);
            if ($specialite) {
                $docteur->getSpecialites()->clear();
                $docteur->getSpecialites()->add($specialite);
            }
        }

        $em->persist($docteur);
        $em->flush();

        return new JsonResponse(['message' => 'Médecin mis à jour avec succès'], Response::HTTP_OK);
    }


    #[Route('/api/docteurs/{id}', name: 'app_docteur_delete', methods: ['DELETE'])]
    public function delete(Docteur $docteur, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($docteur);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }


    #[Route('/api/docteur/{id}/accept', name: 'doctor_rdv_accept', methods: ['PATCH'])]
    public function accept(RendezVous $rendezVous, EntityManagerInterface $em): JsonResponse
    {
        try {
            $rendezVous->setStatut('confirmé');

            // Crée une consultation si elle n'existe pas
            if (!$rendezVous->getConsultation()) {
                $consultation = new Consultation();
                $consultation->setRendezVous($rendezVous);
                $consultation->setType($rendezVous->getTypeConsultation());
                $consultation->setStatut('en attente');
                $dateConsultationAt = $rendezVous->getDateConsultationAt();
                if ($dateConsultationAt instanceof \DateTimeImmutable) {
                    $dateConsultationAt = \DateTime::createFromImmutable($dateConsultationAt);
                }
                $consultation->setDateConsul($dateConsultationAt);
                $heureConsultation = $rendezVous->getHeureConsultation();
                if ($heureConsultation instanceof \DateTimeImmutable) {
                    $heureConsultation = \DateTime::createFromImmutable($heureConsultation);
                }
                $consultation->setHeureConsul($heureConsultation);
                $consultation->setPatient($rendezVous->getPatient());
                $consultation->setCreatedAt(new \DateTimeImmutable());
                $consultation->setUpdatedAt(new \DateTimeImmutable());
                $em->persist($consultation);
            }

            $em->flush();

            return new JsonResponse(['message' => 'Rendez-vous accepté et consultation créée.']);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la mise à jour du rendez-vous',
                'details' => $e->getMessage()
            ], 500);
        }
    }




    #[Route('/api/docteur/{id}/refuse', name: 'doctor_rdv_refuse', methods: ['PATCH'])]
    public function refuse(RendezVous $rendezVous, EntityManagerInterface $em): JsonResponse
    {
        $rendezVous->setStatut('annulé');
        $em->flush();

        return new JsonResponse(['message' => 'Rendez-vous refusé.']);
    }

    #[Route('/api/docteur/{id}/reschedule', name: 'doctor_rvd_reschedule', methods: ['PATCH'])]
    public function reschedule(
        Request $request,
        RendezVous $rendezVous,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['date']) || !isset($data['heure'])) {
            return new JsonResponse(['error' => 'Date et heure requises.'], 400);
        }

        try {
            $date = new \DateTimeImmutable($data['date']);
            $heure = new \DateTimeImmutable($data['heure']);  // <-- conversion obligatoire

            $rendezVous->setDateConsultationAt($date);
            $rendezVous->setHeureConsultation($heure);

            $em->flush();

            return new JsonResponse(['message' => 'Rendez-vous reprogrammé.'], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Données invalides.'], 400);
        }
    }
}
