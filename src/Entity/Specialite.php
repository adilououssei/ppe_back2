<?php

namespace App\Entity;

use App\Repository\SpecialiteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: SpecialiteRepository::class)]
class Specialite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getDocteur", "getSpecialite"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getDocteur", "getSpecialite"])]
    private ?string $nom = null;

    /**
     * @var Collection<int, Docteur>
     */
    #[ORM\ManyToMany(targetEntity: Docteur::class, inversedBy: 'specialites')]
    #[Ignore]
    private Collection $docteur;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["getDocteur", "getSpecialite"])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(["getDocteur", "getSpecialite"])]
    private ?bool $statut = null;

    public function __construct()
    {
        $this->docteur = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * @return Collection<int, Docteur>
     */
    public function getDocteur(): Collection
    {
        return $this->docteur;
    }

    public function addDocteur(Docteur $docteur): static
    {
        if (!$this->docteur->contains($docteur)) {
            $this->docteur->add($docteur);
        }

        return $this;
    }

    public function removeDocteur(Docteur $docteur): static
    {
        $this->docteur->removeElement($docteur);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isStatut(): ?bool
    {
        return $this->statut;
    }

    public function setStatut(bool $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}
