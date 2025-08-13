<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Docteur;
use App\Entity\Patient;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function notifierDocteur(Docteur $docteur, string $message): void
    {
        $statuts = ["lu", "non"];
        $notification = new Notification();
        $notification->setType("demande_rdv");
        $notification->setDocteur($docteur);
        $notification->setMessage($message);
        $notification->setStatut($statuts[array_rand($statuts)]);
        $notification->setDateHeureAt(new \DateTimeImmutable());
        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    public function notifierPatient(Patient $patient, string $message, string $type): void
    {
        $statuts = ["lu", "non"];

        $notification = new Notification();
        $notification->setType($type);
        $notification->setPatient($patient);
        $notification->setMessage($message);
        $notification->setStatut($statuts[array_rand($statuts)]);
        $notification->setDateHeureAt(new \DateTimeImmutable());

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }
}
