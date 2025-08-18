<?php

namespace App\Entity;

use App\Repository\RendezVousRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Attribute\Groups as AttributeGroups;
use Symfony\Component\Serializer\Attribute\MaxDepth;

#[ORM\Entity(repositoryClass: RendezVousRepository::class)]
class RendezVous
{
    public const STATUT_ACCEPTE = 'accepté';
    public const STATUT_REFUSE = 'refusé';
    public const STATUT_ENATTENTE = 'en_attente';
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getRendezVous"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getRendezVous", "getDocteur", "getPatient", "consultation:read"])]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getRendezVous", "getDocteur", "getPatient", "consultation:read"])]
    private ?string $typeConsultation = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["getRendezVous", "getDocteur", "getPatient", "consultation:read"])]
    private ?\DateTimeImmutable $dateConsultationAt = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    #[Groups(["getRendezVous", "getDocteur", "getPatient", "consultation:read"])]
    private ?\DateTimeImmutable $heureConsultation = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getRendezVous", "getDocteur", "getPatient"])]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'rendezVouses')]
    #[Groups(["getRendezVous", "consultation:read"])]
    #[MaxDepth(1)]
    private ?Docteur $docteur = null;

    #[ORM\ManyToOne(inversedBy: 'rendezVouses')]
    #[Groups(["getRendezVous", "getPatient", "consultation:read"])]
    #[MaxDepth(1)]
    private ?Patient $patient = null;

    #[ORM\OneToOne(mappedBy: 'rendezVous', cascade: ['persist', 'remove'])]
    #[Ignore]
    private ?Consultation $consultation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getTypeConsultation(): ?string
    {
        return $this->typeConsultation;
    }

    public function setTypeConsultation(string $typeConsultation): static
    {
        $this->typeConsultation = $typeConsultation;

        return $this;
    }

    public function getDateConsultationAt(): ?\DateTimeImmutable
    {
        return $this->dateConsultationAt;
    }

    public function setDateConsultationAt(?\DateTimeImmutable $dateConsultationAt): static
    {
        $this->dateConsultationAt = $dateConsultationAt;

        return $this;
    }

    public function getHeureConsultation(): ?\DateTimeImmutable
    {
        return $this->heureConsultation;
    }

    public function setHeureConsultation(?\DateTimeImmutable $heureConsultation): static
    {
        $this->heureConsultation = $heureConsultation;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
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

    public function getConsultation(): ?Consultation
    {
        return $this->consultation;
    }

    public function setConsultation(?Consultation $consultation): static
    {
        // unset the owning side of the relation if necessary
        if ($consultation === null && $this->consultation !== null) {
            $this->consultation->setRendezVous(null);
        }

        // set the owning side of the relation if necessary
        if ($consultation !== null && $consultation->getRendezVous() !== $this) {
            $consultation->setRendezVous($this);
        }

        $this->consultation = $consultation;

        return $this;
    }

    
}
