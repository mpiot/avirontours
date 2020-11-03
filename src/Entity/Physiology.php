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

use App\Repository\PhysiologyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=PhysiologyRepository::class)
 */
class Physiology
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\LessThan(propertyPath="heavyAerobicHeartRate")
     */
    private $lightAerobicHeartRate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\GreaterThan(propertyPath="lightAerobicHeartRate")
     * @Assert\LessThan(propertyPath="anaerobicThresholdHeartRate")
     */
    private $heavyAerobicHeartRate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\GreaterThan(propertyPath="heavyAerobicHeartRate")
     * @Assert\LessThan(propertyPath="oxygenTransportationHeartRate")
     */
    private $anaerobicThresholdHeartRate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\GreaterThan(propertyPath="anaerobicThresholdHeartRate")
     * @Assert\LessThan(propertyPath="anaerobicHeartRate")
     */
    private $oxygenTransportationHeartRate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\GreaterThan(propertyPath="oxygenTransportationHeartRate")
     * @Assert\LessThanOrEqual(propertyPath="maximumHeartRate")
     */
    private $anaerobicHeartRate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\GreaterThanOrEqual(100)
     */
    private $maximumHeartRate;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $maximumOxygenConsumption;

    public function __construct(User $user)
    {
        $user->setPhysiology($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLightAerobicHeartRate(): ?int
    {
        return $this->lightAerobicHeartRate;
    }

    public function setLightAerobicHeartRate(?int $lightAerobicHeartRate): self
    {
        $this->lightAerobicHeartRate = $lightAerobicHeartRate;

        return $this;
    }

    public function getHeavyAerobicHeartRate(): ?int
    {
        return $this->heavyAerobicHeartRate;
    }

    public function setHeavyAerobicHeartRate(?int $heavyAerobicHeartRate): self
    {
        $this->heavyAerobicHeartRate = $heavyAerobicHeartRate;

        return $this;
    }

    public function getAnaerobicThresholdHeartRate(): ?int
    {
        return $this->anaerobicThresholdHeartRate;
    }

    public function setAnaerobicThresholdHeartRate(?int $anaerobicThresholdHeartRate): self
    {
        $this->anaerobicThresholdHeartRate = $anaerobicThresholdHeartRate;

        return $this;
    }

    public function getOxygenTransportationHeartRate(): ?int
    {
        return $this->oxygenTransportationHeartRate;
    }

    public function setOxygenTransportationHeartRate(?int $oxygenTransportationHeartRate): self
    {
        $this->oxygenTransportationHeartRate = $oxygenTransportationHeartRate;

        return $this;
    }

    public function getAnaerobicHeartRate(): ?int
    {
        return $this->anaerobicHeartRate;
    }

    public function setAnaerobicHeartRate(?int $anaerobicHeartRate): self
    {
        $this->anaerobicHeartRate = $anaerobicHeartRate;

        return $this;
    }

    public function getMaximumHeartRate(): ?int
    {
        return $this->maximumHeartRate;
    }

    public function setMaximumHeartRate(?int $maximumHeartRate): self
    {
        $this->maximumHeartRate = $maximumHeartRate;

        return $this;
    }

    public function getMaximumOxygenConsumption(): ?float
    {
        return $this->maximumOxygenConsumption;
    }

    public function setMaximumOxygenConsumption(?float $maximumOxygenConsumption): self
    {
        $this->maximumOxygenConsumption = $maximumOxygenConsumption;

        return $this;
    }
}
