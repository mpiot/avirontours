<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SeasonRepository")
 */
class Season
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Length(min="4", max="4")
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @ORM\Column(type="date")
     * @Assert\NotNull()
     */
    private $licenseEndAt;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\SeasonUser", mappedBy="season")
     */
    private $seasonUsers;

    public function __construct()
    {
        $this->seasonUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?int
    {
        return $this->name;
    }

    public function setName(int $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLicenseEndAt(): ?\DateTimeInterface
    {
        return $this->licenseEndAt;
    }

    public function setLicenseEndAt(\DateTimeInterface $licenseEndAt): self
    {
        $this->licenseEndAt = $licenseEndAt;

        return $this;
    }

    /**
     * @return Collection|SeasonUser[]
     */
    public function getSeasonUsers(): Collection
    {
        return $this->seasonUsers;
    }

    public function addSeasonUser(SeasonUser $seasonUser): self
    {
        if (!$this->seasonUsers->contains($seasonUser)) {
            $this->seasonUsers[] = $seasonUser;
            $seasonUser->setSeason($this);
        }

        return $this;
    }

    public function removeSeasonUser(SeasonUser $seasonUser): self
    {
        if ($this->seasonUsers->contains($seasonUser)) {
            $this->seasonUsers->removeElement($seasonUser);
            // set the owning side to null (unless already changed)
            if ($seasonUser->getSeason() === $this) {
                $seasonUser->setSeason(null);
            }
        }

        return $this;
    }
}
