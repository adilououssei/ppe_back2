<?php

namespace App\Controller;

use App\Entity\Docteur;
use App\Entity\RendezVous;
use App\Entity\Consultation;
use App\Repository\DocteurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class DocteurController extends AbstractController
{
    #[Route('/api/docteurs', name: 'app_docteur', methods: ['GET'])]
    public function ListeDocteur(DocteurRepository $docteurRepository, SerializerInterface $serializer): JsonResponse
    {

        $docteurs = $docteurRepository->findAll();
        $jsonDocteur = $serializer->serialize($docteurs, 'json', ['groups' => 'getDocteur']);
        return new JsonResponse($jsonDocteur, Response::HTTP_OK, [], true);
    }

    #[Route('/api/docteur/{id}', name: 'app_docteur_show', methods: ['GET'])]
    public function show(Docteur $docteur, SerializerInterface $serializer): JsonResponse
    {
        $jsonDocteur = $serializer->serialize($docteur, 'json', ['groups' => 'getDocteur']);
        return new JsonResponse($jsonDocteur, Response::HTTP_OK, [], true);
    }

    #[Route('/api/docteur', name: 'app_docteur_create', methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $docteur = $serializer->deserialize($request->getContent(), Docteur::class, 'json');
        $em->persist($docteur);
        $em->flush();

        $location = $urlGenerator->generate('app_docteur_show', ['id' => $docteur->getId()]);

        $jsonDocteur = $serializer->serialize($docteur, 'json', ['groups' => 'getDocteur']);
        return new JsonResponse($jsonDocteur, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/docteur/{id}', name: 'app_docteur_delete', methods: ['DELETE'])]
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
