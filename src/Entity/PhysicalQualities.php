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

use App\Repository\PhysicalQualitiesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PhysicalQualitiesRepository::class)]
class PhysicalQualities
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\GreaterThanOrEqual(value: '0')]
    #[Assert\LessThanOrEqual(value: '20')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $proprioception = null;

    #[Assert\GreaterThanOrEqual(value: '0')]
    #[Assert\LessThanOrEqual(value: '20')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $weightPowerRatio = null;

    #[Assert\GreaterThanOrEqual(value: '0')]
    #[Assert\LessThanOrEqual(value: '20')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $explosiveStrength = null;

    #[Assert\GreaterThanOrEqual(value: '0')]
    #[Assert\LessThanOrEqual(value: '20')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $enduranceStrength = null;

    #[Assert\GreaterThanOrEqual(value: '0')]
    #[Assert\LessThanOrEqual(value: '20')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $maximumStrength = null;

    #[Assert\GreaterThanOrEqual(value: '0')]
    #[Assert\LessThanOrEqual(value: '20')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $stressResistance = null;

    #[Assert\GreaterThanOrEqual(value: '0')]
    #[Assert\LessThanOrEqual(value: '20')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $coreStrength = null;

    #[Assert\GreaterThanOrEqual(value: '0')]
    #[Assert\LessThanOrEqual(value: '20')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $flexibility = null;

    #[Assert\GreaterThanOrEqual(value: '0')]
    #[Assert\LessThanOrEqual(value: '20')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $recovery = null;

    public function __construct(User $user)
    {
        $user->setPhysicalQualities($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProprioception(): ?int
    {
        return $this->proprioception;
    }

    public function setProprioception(int $proprioception): self
    {
        $this->proprioception = $proprioception;

        return $this;
    }

    public function getWeightPowerRatio(): ?int
    {
        return $this->weightPowerRatio;
    }

    public function setWeightPowerRatio(int $weightPowerRatio): self
    {
        $this->weightPowerRatio = $weightPowerRatio;

        return $this;
    }

    public function getExplosiveStrength(): ?int
    {
        return $this->explosiveStrength;
    }

    public function setExplosiveStrength(int $explosiveStrength): self
    {
        $this->explosiveStrength = $explosiveStrength;

        return $this;
    }

    public function getEnduranceStrength(): ?int
    {
        return $this->enduranceStrength;
    }

    public function setEnduranceStrength(int $enduranceStrength): self
    {
        $this->enduranceStrength = $enduranceStrength;

        return $this;
    }

    public function getMaximumStrength(): ?int
    {
        return $this->maximumStrength;
    }

    public function setMaximumStrength(int $maximumStrength): self
    {
        $this->maximumStrength = $maximumStrength;

        return $this;
    }

    public function getStressResistance(): ?int
    {
        return $this->stressResistance;
    }

    public function setStressResistance(int $stressResistance): self
    {
        $this->stressResistance = $stressResistance;

        return $this;
    }

    public function getCoreStrength(): ?int
    {
        return $this->coreStrength;
    }

    public function setCoreStrength(int $coreStrength): self
    {
        $this->coreStrength = $coreStrength;

        return $this;
    }

    public function getFlexibility(): ?int
    {
        return $this->flexibility;
    }

    public function setFlexibility(int $flexibility): self
    {
        $this->flexibility = $flexibility;

        return $this;
    }

    public function getRecovery(): ?int
    {
        return $this->recovery;
    }

    public function setRecovery(int $recovery): self
    {
        $this->recovery = $recovery;

        return $this;
    }
}
