<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Docteur;
use App\Entity\Patient;
use App\Entity\RendezVous;
use App\Entity\Specialite;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;
    private $batchSize = 30; // Réduire la taille des lots

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadUsersAndPatients($manager);
        $this->loadSpecialites($manager);
        $this->loadDocteurs($manager);
        // $this->loadDoctors($manager);
        $this->loadAppointments($manager);
        $this->loadAdmin($manager);
    }

    // Créer un administrateur
    public function loadAdmin(ObjectManager $manager)
    {
    $noms = ['Dupont', 'Durand', 'Leroy', 'Moreau', 'Simon'];
    $prenoms = ['Alice', 'Bob', 'Claire', 'David', 'Emma'];

    // Création des administrateurs
    for ($i = 1; $i <= 3; $i++) {
        // Création d'un administrateur
        $user = new User();
        $user->setEmail("admin{$i}@example.com")
                ->setPassword($this->userPasswordHasher->hashPassword($user, "admin{$i}"))
                ->setRoles(['ROLE_ADMIN']); // Attribuer le rôle ROLE_ADMIN

        // Persister l'administrateur
        $manager->persist($user);
    }
    // Sauvegarde dans la base de données
    $manager->flush();
    $manager->clear();
    }

    public function loadUsersAndPatients(ObjectManager $manager)
    {
        $noms = ['Dupont', 'Durand', 'Leroy', 'Moreau', 'Simon'];
        $prenoms = ['Alice', 'Bob', 'Claire', 'David', 'Emma'];

        // Création de 20 patients
        for ($i = 1; $i <= 20; $i++) {
            // Création de l'utilisateur (patient)
            $user = new User();
            $user->setEmail("patient{$i}@example.com")
                 ->setPassword($this->userPasswordHasher->hashPassword($user, "patient{$i}"))
                 ->setRoles(['ROLE_PATIENT']); // Attribuer le rôle ROLE_PATIENT

            // Persister l'utilisateur
            $manager->persist($user);

            // Création du patient
            $patient = new Patient();
            $patient->setNom($noms[array_rand($noms)])
                    ->setPrenom($prenoms[array_rand($prenoms)])
                    ->setTelephone('06' . rand(10000000, 99999999))
                    ->setAdresse("Adresse $i, Ville")
                    ->setUser($user); // Lier le patient à l'utilisateur (user)

            // Persister le patient
            $manager->persist($patient);
        }

        // Sauvegarde dans la base de données
        $manager->flush();
        $manager->clear();
    }

    // private function loadDoctors(ObjectManager $manager): void
    // {
    //     $specialites = ['Cardiologie', 'Orthopédie', 'Gastro-entérologie'];
    //     $listeSpecialites = array_map(function ($nom) use ($manager) {
    //         $spec = new Specialite();
    //         $spec->setNom($nom);
    //         $manager->persist($spec);
    //         return $spec;
    //     }, $specialites);

    //     $manager->flush();
    //     $manager->clear();

    //     // ... (code similaire pour les docteurs)
    // }

    private function loadAppointments(ObjectManager $manager): void
    {
        $patients = $manager->getRepository(Patient::class)->findAll();
        $docteurs = $manager->getRepository(Docteur::class)->findAll();
        $type_consulations = ["en ligne", "à l'hopital", "à la maison"];
        $statuts =[RendezVous::STATUT_ACCEPTE, RendezVous::STATUT_REFUSE, RendezVous::STATUT_ENATTENTE];
        for ($i = 1; $i <= 50; $i++) {
            $rdv = new RendezVous();
            $rdv->setDescription("Consultation $i");
            if(!empty($patients)){
                $rdv->setPatient($patients[array_rand($patients)]);
                
            }
            if(!empty($docteurs)){
                $rdv->setDocteur($docteurs[array_rand($docteurs)]);
            }
            $rdv->setTypeConsultation($type_consulations[array_rand($type_consulations)]);
            $rdv->setStatut($statuts[array_rand($statuts)]);
            $rdv->setDateConsultationAt(new \DateTimeImmutable());
            $rdv->setHeureConsultation(new \DateTimeImmutable());
            $manager->persist($rdv);

            if ($i % $this->batchSize === 0) {
                $manager->flush();
                $manager->clear();
                $patients = $manager->getRepository(Patient::class)->findAll();
                $docteurs = $manager->getRepository(Docteur::class)->findAll();
            }
        }
        $manager->flush();
        $manager->clear();
    }

    // créer quelques spécialités
    private function loadSpecialites(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $specialite = new Specialite();
            $specialite->setNom("Spécialité $i");
            $manager->persist($specialite);
        }
        $manager->flush();
        $manager->clear();
    }

    // créer quelques docteurs avec leurs spécialités
    public function loadDocteurs(ObjectManager $manager)
    {
        // Récupérer les spécialités déjà existantes
        $specialites = $manager->getRepository(Specialite::class)->findAll();

        // Tableaux de noms et prénoms pour créer des docteurs
        $noms = ['Dupont', 'Durand', 'Leroy', 'Moreau', 'Simon'];
        $prenoms = ['Alice', 'Bob', 'Claire', 'David', 'Emma'];

        // Création de 20 docteurs
        for ($i = 1; $i <= 20; $i++) {
            // Création de l'utilisateur (docteur)
            $user = new User();
            $user->setEmail('docteur' . $i . '@example.com')
                ->setPassword($this->userPasswordHasher->hashPassword($user, 'docteurmotdepasse')) // motdepasse par exemple
                ->setRoles(['ROLE_DOCTEUR']); // Rôle de docteur

            // Création du docteur et association avec l'utilisateur
            $docteur = new Docteur();
            $docteur->setNom($noms[array_rand($noms)])
                    ->setPrenom($prenoms[array_rand($prenoms)])
                    ->setTelephone('06' . rand(10000000, 99999999))
                    ->addSpecialite($specialites[array_rand($specialites)])
                    ->setUser($user); // Associer l'utilisateur au docteur

            // Persister les entités
            $manager->persist($user); // Persist l'utilisateur (User)
            $manager->persist($docteur); // Persist le docteur (Docteur)
        }

        // Sauvegarde dans la base de données
        $manager->flush();
    }
}