<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use App\Entity\Docteur;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getUser", "getPatient","getRendezvous"])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(["getUser", "getPatient","getRendezvous", "getDocteur"])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(["getUser", "getPatient","getRendezvous"])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;
    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(["getUser"])]
    private ?Patient $patient = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(["getDocteur"])]
    #[Ignore]
    private ?Docteur $docteur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): static
    {
        // unset the owning side of the relation if necessary
        if ($patient === null && $this->patient !== null) {
            $this->patient->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($patient !== null && $patient->getUser() !== $this) {
            $patient->setUser($this);
        }

        $this->patient = $patient;

        return $this;
    }

    public function getDocteur(): ?Docteur
    {
        return $this->docteur;
    }

    public function setDocteur(?Docteur $docteur): static
    {
        // unset the owning side of the relation if necessary
        if ($docteur === null && $this->docteur !== null) {
            $this->docteur->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($docteur !== null && $docteur->getUser() !== $this) {
            $docteur->setUser($this);
        }

        $this->docteur = $docteur;

        return $this;
    }

    

    
}
