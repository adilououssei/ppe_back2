<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Patient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            // Désérialisation de l'utilisateur
            $user = $serializer->deserialize($request->getContent(), User::class, 'json');
            $patient = $serializer->deserialize($request->getContent(), Patient::class, 'json');
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Données invalides ou mal formatées : ' . $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user->setPatient($patient);

        
        // Extraction du mot de passe brut
        $data = json_decode($request->getContent(), true);
        $plainPassword = $data['password'] ?? null;

        if (!$plainPassword) {
            return new JsonResponse(
                ['error' => 'Le mot de passe est requis'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Hashage du mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        // Vérification des erreurs de validation
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse(
                ['error' => (string) $errors],
                Response::HTTP_BAD_REQUEST
            );
        }

        

        try {
            // Enregistrement dans la base de données
            $em->persist($user);
            $em->persist($patient);
            $em->flush();

            return new JsonResponse([
                'message' => 'Inscription réussie',
                'userId' => $user->getId(),
                'patientId' => $patient->getId()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Erreur lors de la création du compte : ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
