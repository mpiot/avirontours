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

use App\Repository\AnatomyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AnatomyRepository::class)]
class Anatomy
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $height = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $weight = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $armSpan = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $bustLength = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $legLength = null;

    public function __construct(User $user)
    {
        $user->setAnatomy($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getArmSpan(): ?int
    {
        return $this->armSpan;
    }

    public function setArmSpan(?int $armSpan): self
    {
        $this->armSpan = $armSpan;

        return $this;
    }

    public function getBustLength(): ?int
    {
        return $this->bustLength;
    }

    public function setBustLength(?int $bustLength): self
    {
        $this->bustLength = $bustLength;

        return $this;
    }

    public function getLegLength(): ?int
    {
        return $this->legLength;
    }

    public function setLegLength(?int $legLength): self
    {
        $this->legLength = $legLength;

        return $this;
    }
}
