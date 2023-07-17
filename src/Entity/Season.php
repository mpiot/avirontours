<?php

declare(strict_types=1);

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

use App\Repository\SeasonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity('name')]
#[ORM\Entity(repositoryClass: SeasonRepository::class)]
class Season
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 4, max: 4)]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $name = null;

    #[Assert\Count(min: 1)]
    #[ORM\OneToMany(mappedBy: 'season', targetEntity: 'App\Entity\SeasonCategory', cascade: ['persist', 'remove'])]
    private Collection $seasonCategories;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $subscriptionEnabled = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $active = false;

    public function __construct()
    {
        $this->seasonCategories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?int
    {
        return $this->name;
    }

    public function getExtendedName(): string
    {
        return ($this->name - 1).' - '.$this->name;
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
