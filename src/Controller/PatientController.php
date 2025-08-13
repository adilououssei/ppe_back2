<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Repository\PatientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class PatientController extends AbstractController
{
    #[Route('/api/patients', name: 'app_patient', methods:['GET'])]
           public function ListePatient(PatientRepository $patient, SerializerInterface $serializer): JsonResponse
    {
        $patients = $patient->findAll();
        $jsonSpecialite = $serializer->serialize($patients, 'json');
        return new JsonResponse($jsonSpecialite, Response::HTTP_OK, [], true);
    }

    #[Route('/api/patient/{id}', name: 'app_patient_show', methods:['GET'])]
    public function show(Patient $patient, SerializerInterface $serializer): JsonResponse
    {
        $jsonPatient = $serializer->serialize($patient, 'json',);
        return new JsonResponse($jsonPatient, Response::HTTP_OK, [], true);
    }

    #[Route('/api/patient', name: 'app_patient_create', methods:['POST'])]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $patient = $serializer->deserialize($request->getContent(), Patient::class, 'json');
        $em->persist($patient);
        $em->flush();

        $location = $urlGenerator->generate('app_patient_show', ['id' => $patient->getId()]);

        $jsonPatient = $serializer->serialize($patient, 'json');
        return new JsonResponse($jsonPatient, Response::HTTP_CREATED, ["Location"=>$location], true);
    }

    #[Route('/api/patient/{id}', name: 'app_patient_delete', methods:['DELETE'])]
    public function delete(Patient $patient, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($patient);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
