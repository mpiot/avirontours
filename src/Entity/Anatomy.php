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

use App\Repository\AnatomyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AnatomyRepository::class)
 */
class Anatomy
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $height;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $weight;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $armSpan;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $bustLength;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $legLength;

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
