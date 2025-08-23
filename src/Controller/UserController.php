<?php

// namespace App\Controller;

// use Symfony\Bundle\SecurityBundle\Security;
// use Symfony\Component\Routing\Annotation\Route;
// use Symfony\Component\HttpFoundation\JsonResponse;
// use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// class UserController extends AbstractController
// {
//     // Route pour récupérer les informations de l'utilisateur connecté
//     #[Route('/api/me', name: 'api_me', methods: ['GET'])]
//     public function me(Security $security): JsonResponse
//     {
//         // Récupérer l'utilisateur connecté
//         $user = $security->getUser();

//         // Si aucun utilisateur n'est connecté
//         if (!$user) {
//             return new JsonResponse(['error' => 'User not authenticated'], 401);
//         }

//         // Retourner les informations de l'utilisateur
//         return new JsonResponse([
//             'id' => $user->getUserIdentifier(),
//             'email' => $user->getUserIdentifier(),
//             'roles' => $user->getRoles(),
//         ]);
//     }

// src/Controller/Api/UserController.php
namespace App\Controller;

use App\Entity\User;

use App\Repository\UserRepository;
use App\Repository\DocteurRepository;
use App\Repository\PatientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\SecurityBundle\Security;


class UserController extends AbstractController
{
    private $em;
    private $userRepo;
    private $docteurRepo;
    private $patientRepo;
    private $serializer;
    private $passwordHasher;

    public function __construct(
        EntityManagerInterface $em,
        UserRepository $userRepo,
        DocteurRepository $docteurRepo,
        PatientRepository $patientRepo,
        SerializerInterface $serializer,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->em = $em;
        $this->userRepo = $userRepo;
        $this->docteurRepo = $docteurRepo;
        $this->patientRepo = $patientRepo;
        $this->serializer = $serializer;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/api/users', name: 'api_users_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $users = $this->userRepo->findAll();

        // Préparer un tableau avec infos user + liées selon role
        $data = array_map(function (User $user) {
            $base = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $user->getRoles(),
                // n'affiche pas password
            ];

            if ($user->getRoles() === 'docteur') {
                $doc = $this->docteurRepo->findOneBy(['user' => $user]);
                if ($doc) {
                    $base['prenom'] = $doc->getPrenom();
                    $base['nom'] = $doc->getNom();
                    $base['specialtes'] = $doc->getSpecialtes();
                    $base['status'] = $doc->getStatus();
                    $base['lastLogin'] = $doc->getLastLogin()?->format('Y-m-d');
                }
            } elseif ($user->getRoles() === 'patient') {
                $pat = $this->patientRepo->findOneBy(['user' => $user]);
                if ($pat) {
                    $base['prenom'] = $pat->getPrenom();
                    $base['nom'] = $pat->getNom();
                    $base['status'] = $pat->getStatus();
                    $base['lastLogin'] = $pat->getLastLogin()?->format('Y-m-d');
                }
            } else {
                // Admin, peut avoir des champs spécifiques ou non
                $base['prenom'] = 'Admin';
                $base['nom'] = 'System';
                $base['status'] = 'active';
                $base['lastLogin'] = null;
            }

            return $base;
        }, $users);

        return $this->json($data);
    }

    #[Route('/api/users', name: 'api_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'], $data['role'])) {
            return $this->json(['error' => 'Champs manquants'], 400);
        }

        $user = new User();
        $user->setEmail($data['email']);
        // Role doit être un tableau, par ex: ['ROLE_DOCTOR']
        $user->setRoles([$data['role']]);
        $hashed = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashed);

        $this->em->persist($user);
        $this->em->flush();

        // Vérifier si rôle contient ROLE_DOCTOR
        if (in_array('ROLE_DOCTOR', $user->getRoles(), true)) {
            $doctor = new \App\Entity\Docteur();
            $doctor->setUser($user);
            $doctor->setPrenom($data['prenom'] ?? '');
            $doctor->setNom($data['nom'] ?? '');
            // Gestion ManyToMany pour spécialités
            if (!empty($data['specialties']) && is_array($data['specialties'])) {
                foreach ($data['specialties'] as $specId) {
                    $specialite = $this->em->getRepository(\App\Entity\Specialite::class)->find($specId);
                    if ($specialite) {
                        $doctor->addSpecialite($specialite);  // méthode addSpecialite() dans Docteur
                    }
                }
            }
            // Pas de champ 'status' ? supprimer ou adapter
            // $doctor->setStatus('active'); // <- Supprimer si n'existe pas
            $this->em->persist($doctor);
        } elseif (in_array('ROLE_PATIENT', $user->getRoles(), true)) {
            $patient = new \App\Entity\Patient();
            $patient->setUser($user);
            $patient->setPrenom($data['prenom'] ?? '');
            $patient->setNom($data['nom'] ?? '');
            // Pas de champ 'statut' ? supprimer ou adapter
            // $patient->setStatut('active'); // <- Supprimer si n'existe pas
            $this->em->persist($patient);
        }

        $this->em->flush();

        return $this->json(['id' => $user->getId()], 201);
    }


    #[Route('/api/users/{id}', name: 'api_users_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepo->find($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) $user->setEmail($data['email']);
        if (isset($data['role'])) $user->setRole($data['role']);
        if (isset($data['password']) && $data['password'] !== '') {
            $hashed = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashed);
        }

        // Mettre à jour Docteur ou Patient
        if ($user->getRole() === 'doctor') {
            $doctor = $this->docteurRepo->findOneBy(['user' => $user]);
            if ($doctor) {
                if (isset($data['prenom'])) $doctor->setPrenom($data['prenom']);
                if (isset($data['nom'])) $doctor->setNom($data['nom']);
                if (isset($data['specialty'])) $doctor->setSpecialty($data['specialty']);
                $this->em->persist($doctor);
            }
        } elseif ($user->getRole() === 'patient') {
            $patient = $this->patientRepo->findOneBy(['user' => $user]);
            if ($patient) {
                if (isset($data['prenom'])) $patient->setPrenom($data['prenom']);
                if (isset($data['nom'])) $patient->setNom($data['nom']);
                $this->em->persist($patient);
            }
        }

        $this->em->persist($user);
        $this->em->flush();

        return $this->json(['message' => 'Utilisateur mis à jour']);
    }

    #[Route('/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->userRepo->find($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $this->em->remove($user);
        $this->em->flush();

        return $this->json(['message' => 'Utilisateur supprimé']);
    }

    // Route pour récupérer les informations de l'utilisateur connecté
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(Security $security): JsonResponse
    {
        /** @var User|null $user */
        $user = $security->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        $response = [
            'id' => $user->getUserIdentifier(),
            'email' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ];

        // Vérifie si le user est un docteur et s’il a un objet Docteur lié
        if (in_array('ROLE_DOCTOR', $user->getRoles()) && $user->getDocteur()) {
            $docteur = $user->getDocteur();
            $response['nom'] = $docteur->getNom();
            $response['prenom'] = $docteur->getPrenom();
            $response['telephone'] = $docteur->getTelephone();
        }

        // Pour l’admin
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $response['nom'] = 'Administrateur';
            $response['prenom'] = '';
            $response['telephone'] = '';
        }

        return new JsonResponse($response);
    }
}
