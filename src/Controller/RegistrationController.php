<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Patient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password']) || empty($data['nom']) || empty($data['prenom'])) {
            return new JsonResponse(['message' => 'Champs manquants'], 400);
        }

        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse(['message' => 'Cet email est déjà utilisé'], 409);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setRoles(['ROLE_PATIENT']);
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $patient = new Patient();
        $patient->setUser($user);
        $patient->setNom($data['nom']);
        $patient->setPrenom($data['prenom']);
        $patient->setAdresse($data['adresse'] ?? null);
        $patient->setTelephone($data['telephone'] ?? null);

        $user->setPatient($patient);

        $em->persist($user);
        $em->persist($patient);
        $em->flush();

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'message' => 'Inscription réussie',
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'nom' => $patient->getNom(),
                'prenom' => $patient->getPrenom(),
            ]
        ], 201);
    }
}
