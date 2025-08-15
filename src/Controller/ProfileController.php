<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Docteur;
use App\Entity\Patient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileController extends AbstractController
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    #[Route('/api/profile', name: 'api_profile', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ];

        // Vérifier le rôle et ajouter les infos spécifiques
        if (in_array('ROLE_DOCTEUR', $user->getRoles(), true) && $user->getDocteur() instanceof Docteur) {
            $docteur = $user->getDocteur();
            $data['nom'] = $docteur->getNom();
            $data['prenom'] = $docteur->getPrenom();
            $data['telephone'] = $docteur->getTelephone();
            $data['specialites'] = [];
            foreach ($docteur->getSpecialites() as $specialite) {
                $data['specialites'][] = [
                    'id' => $specialite->getId(),
                    'nom' => $specialite->getNom()
                ];
            }
        } elseif (in_array('ROLE_PATIENT', $user->getRoles(), true) && $user->getPatient() instanceof Patient) {
            $patient = $user->getPatient();
            $data['nom'] = $patient->getNom();
            $data['prenom'] = $patient->getPrenom();
            $data['telephone'] = $patient->getTelephone();
            $data['adresse'] = $patient->getAdresse();
        }

        // L'administrateur ne récupère que email, id et roles
        $json = $this->serializer->serialize($data, 'json');

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/api/profile/update', name: 'api_profile_update', methods: ['PUT'])]
    public function updateProfile(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (in_array('ROLE_DOCTEUR', $user->getRoles(), true) && $user->getDocteur() instanceof Docteur) {
            $docteur = $user->getDocteur();
            $docteur->setNom($data['nom'] ?? $docteur->getNom());
            $docteur->setPrenom($data['prenom'] ?? $docteur->getPrenom());
            $docteur->setTelephone($data['telephone'] ?? $docteur->getTelephone());
            // Pour les spécialités, gérer avec add/remove si nécessaire
        } elseif (in_array('ROLE_PATIENT', $user->getRoles(), true) && $user->getPatient() instanceof Patient) {
            $patient = $user->getPatient();
            $patient->setNom($data['nom'] ?? $patient->getNom());
            $patient->setPrenom($data['prenom'] ?? $patient->getPrenom());
            $patient->setTelephone($data['telephone'] ?? $patient->getTelephone());
            $patient->setAdresse($data['adresse'] ?? $patient->getAdresse());
        }

        $em->flush(); // <-- flush via EntityManager injecté

        return new JsonResponse(['message' => 'Profil mis à jour avec succès'], Response::HTTP_OK);
    }

    #[Route('/api/profile/change-password', name: 'api_profile_change_password', methods: ['PUT'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $oldPassword = $data['oldPassword'] ?? '';
        $newPassword = $data['newPassword'] ?? '';

        if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
            return new JsonResponse(['error' => 'Ancien mot de passe incorrect'], Response::HTTP_BAD_REQUEST);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        $em->flush(); // <-- flush via EntityManager injecté

        return new JsonResponse(['message' => 'Mot de passe mis à jour avec succès'], Response::HTTP_OK);
    }
}
    

