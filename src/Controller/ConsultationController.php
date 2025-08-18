<?php
// src/Controller/ConsultationController.php

namespace App\Controller;

use App\Entity\Docteur;
use App\Entity\Patient;
use App\Entity\Consultation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/consultations')]
class ConsultationController extends AbstractController
{
    private Security $security;
    private EntityManagerInterface $em;
    private SerializerInterface $serializer;

    public function __construct(Security $security, EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->security = $security;
        $this->em = $em;
        $this->serializer = $serializer;
    }

    /**
     * Récupère toutes les consultations en ligne confirmées du docteur connecté
     */
    #[Route('/online', name: 'api_consultations_online', methods: ['GET'])]
    public function getConsultationsEnLigne(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $docteur = $this->em->getRepository(Docteur::class)->findOneBy(['user' => $user]);
        if (!$docteur) {
            return new JsonResponse(['error' => 'Accès réservé aux docteurs'], 403);
        }

        // On filtre directement sur Consultation
        $consultations = $this->em->getRepository(Consultation::class)
            ->createQueryBuilder('c')
            ->innerJoin('c.rendezVous', 'r')
            ->where('r.docteur = :docteur')
            ->andWhere('LOWER(c.type) LIKE :type OR LOWER(r.typeConsultation) LIKE :type')
            ->andWhere('c.statut != :statutTermine')
            ->setParameter('docteur', $docteur)
            ->setParameter('type', 'en_ligne')
            ->setParameter('statutTermine', 'terminé')
            ->orderBy('c.dateConsul', 'ASC')
            ->addOrderBy('c.heureConsul', 'ASC')
            ->getQuery()
            ->getResult();


        $json = $this->serializer->serialize($consultations, 'json', [
            'groups' => ['consultation:read', 'getRendezVous', 'getPatient'],
            'enable_max_depth' => true
        ]);

        return new JsonResponse($json, 200, [], true);
    }



    /**
     * Termine une consultation
     */
    #[Route('/{id}/complete', name: 'api_consultation_complete', methods: ['PUT'])]
    public function completeConsultation(int $id, Request $request): JsonResponse
    {
        $consultation = $this->em->getRepository(Consultation::class)->find($id);
        if (!$consultation) {
            return new JsonResponse(['error' => 'Consultation non trouvée'], 404);
        }

        $user = $this->security->getUser();
        $docteur = $this->em->getRepository(Docteur::class)->findOneBy(['user' => $user]);

        if (!$docteur || $consultation->getRendezVous()->getDocteur() !== $docteur) {
            return new JsonResponse(['error' => 'Action non autorisée'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $consultation->setStatut('terminé');
        $consultation->setPrescription($data['prescription'] ?? '');
        $consultation->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return new JsonResponse(['message' => 'Consultation terminée avec succès']);
    }

    #[Route('/patient/online', name: 'api_patient_consultations_online', methods: ['GET'])]
    public function getPatientConsultationsEnLigne(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $patient = $this->em->getRepository(Patient::class)->findOneBy(['user' => $user]);
        if (!$patient) {
            return new JsonResponse(['error' => 'Accès réservé aux patients'], Response::HTTP_FORBIDDEN);
        }

        $consultations = $this->em->getRepository(Consultation::class)
            ->createQueryBuilder('c')
            ->innerJoin('c.rendezVous', 'r')
            ->where('c.patient = :patient')
            ->andWhere('LOWER(c.type) LIKE :type OR LOWER(r.typeConsultation) LIKE :type')
            ->andWhere('c.statut != :statutTermine')
            ->setParameter('patient', $patient)
            ->setParameter('type', 'en_ligne')
            ->setParameter('statutTermine', 'terminé')
            ->orderBy('c.dateConsul', 'ASC')
            ->addOrderBy('c.heureConsul', 'ASC')
            ->getQuery()
            ->getResult();


        $json = $this->serializer->serialize($consultations, 'json', [
            'groups' => ['consultation:read', 'getRendezVous', 'getDocteur']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
