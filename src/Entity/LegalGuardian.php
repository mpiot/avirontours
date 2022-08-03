<?php

namespace App\Entity;

use App\Enum\LegalGuardianRole;
use App\Repository\LegalGuardianRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LegalGuardianRepository::class)]
class LegalGuardian
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotNull]
    #[ORM\Column(length: 255, enumType: LegalGuardianRole::class)]
    private ?LegalGuardianRole $role = null;

    #[Assert\NotBlank]
    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[Assert\NotBlank]
    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[Assert\NotBlank]
    #[ORM\Column(length: 255)]
    private ?string $phoneNumber = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRole(): ?LegalGuardianRole
    {
        return $this->role;
    }

    public function setRole(?LegalGuardianRole $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }
}
