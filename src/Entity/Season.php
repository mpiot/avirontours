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
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Length(min="4", max="4")
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\SeasonCategory", mappedBy="season", cascade={"persist", "remove"})
     * @Assert\Count(min="1")
     */
    private $seasonCategories;

    /**
     * @ORM\Column(type="boolean")
     */
    private $subscriptionEnabled;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    public function __construct()
    {
        $this->seasonCategories = new ArrayCollection();
        $this->subscriptionEnabled = false;
        $this->active = false;
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

    /**
     * @return Collection|SeasonCategory[]
     */
    public function getSeasonCategories(): Collection
    {
        return $this->seasonCategories;
    }

    public function addSeasonCategory(SeasonCategory $seasonCategory): self
    {
        if (!$this->seasonCategories->contains($seasonCategory)) {
            $this->seasonCategories[] = $seasonCategory;
            $seasonCategory->setSeason($this);
        }

        return $this;
    }

    public function removeSeasonCategory(SeasonCategory $seasonCategory): self
    {
        if ($this->seasonCategories->contains($seasonCategory)) {
            $this->seasonCategories->removeElement($seasonCategory);
            // set the owning side to null (unless already changed)
            if ($seasonCategory->getSeason() === $this) {
                $seasonCategory->setSeason(null);
            }
        }

        return $this;
    }

    public function getSubscriptionEnabled(): ?bool
    {
        return $this->subscriptionEnabled;
    }

    public function setSubscriptionEnabled(bool $subscriptionEnabled): self
    {
        $this->subscriptionEnabled = $subscriptionEnabled;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}
