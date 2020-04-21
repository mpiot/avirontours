<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SeasonUserRepository")
 * @UniqueEntity(fields={"season", "user"}, message="Déjà inscrit pour cette saison.")
 */
class SeasonUser
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Season", inversedBy="seasonUsers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $season;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="seasonUsers")
     * @ORM\JoinColumn(name="app_user", nullable=false)
     */
    private $user;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\MedicalCertificate", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     * @Assert\Valid()
     */
    private $medicalCertificate;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(?Season $season): self
    {
        $this->season = $season;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getMedicalCertificate(): ?MedicalCertificate
    {
        return $this->medicalCertificate;
    }

    public function setMedicalCertificate(MedicalCertificate $medicalCertificate): self
    {
        $this->medicalCertificate = $medicalCertificate;

        return $this;
    }
}
