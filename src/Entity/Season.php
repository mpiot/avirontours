<?php

/*
 * Copyright 2020 Mathieu Piot
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
