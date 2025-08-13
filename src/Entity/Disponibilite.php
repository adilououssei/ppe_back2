<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\DisponibiliteRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DisponibiliteRepository::class)]
class Disponibilite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('disponibilite')]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups('disponibilite')]
    private ?\DateTime $date = null;

    #[ORM\ManyToOne(inversedBy: 'disponibilites')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('disponibilite')]
    private ?Docteur $docteur = null;

    /**
     * @var Collection<int, Creneau>
     */
    #[ORM\OneToMany(targetEntity: Creneau::class, mappedBy: 'disponibilite', orphanRemoval: true)]
    #[Groups('disponibilite')]
    private Collection $creneaus;

    public function __construct()
    {
        $this->creneaus = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

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

    /**
     * @return Collection<int, Creneau>
     */
    public function getCreneaus(): Collection
    {
        return $this->creneaus;
    }

    public function addCreneau(Creneau $creneau): static
    {
        if (!$this->creneaus->contains($creneau)) {
            $this->creneaus->add($creneau);
            $creneau->setDisponibilite($this);
        }

        return $this;
    }

    public function removeCreneau(Creneau $creneau): static
    {
        if ($this->creneaus->removeElement($creneau)) {
            // set the owning side to null (unless already changed)
            if ($creneau->getDisponibilite() === $this) {
                $creneau->setDisponibilite(null);
            }
        }

        return $this;
    }
}
