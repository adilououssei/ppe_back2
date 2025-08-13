<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getNotification"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getNotification"])]
    private ?string $message = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["getNotification"])]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["getNotification"])]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    private ?Docteur $docteur = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    private ?Patient $patient = null;

    #[ORM\Column]
    #[Groups(["getNotification"])]
    private ?\DateTimeImmutable $dateHeureAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDocteur(): ?Docteur
    {
        return $this->docteur;
    }

    public function setDocteur(?Docteur $docteur): static
    {
        $this->docteur = $docteur;

        return $this;
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): static
    {
        $this->patient = $patient;

        return $this;
    }

    public function getDateHeureAt(): ?\DateTimeImmutable
    {
        return $this->dateHeureAt;
    }

    public function setDateHeureAt(\DateTimeImmutable $dateHeureAt): static
    {
        $this->dateHeureAt = $dateHeureAt;

        return $this;
    }
}
