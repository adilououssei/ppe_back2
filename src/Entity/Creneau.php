<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CreneauRepository;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CreneauRepository::class)]
class Creneau
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('creneau')]
    private ?int $id = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups('creneau', 'disponibilite')]
    private ?\DateTime $debut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups('creneau', 'disponibilite')]
    private ?\DateTime $fin = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('creneau')]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'creneaus')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('creneau')]
    private ?Disponibilite $disponibilite = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDebut(): ?\DateTime
    {
        return $this->debut;
    }

    public function setDebut(\DateTime $debut): static
    {
        $this->debut = $debut;

        return $this;
    }

    public function getFin(): ?\DateTime
    {
        return $this->fin;
    }

    public function setFin(\DateTime $fin): static
    {
        $this->fin = $fin;

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

    public function getDisponibilite(): ?Disponibilite
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(?Disponibilite $disponibilite): static
    {
        $this->disponibilite = $disponibilite;

        return $this;
    }
}
