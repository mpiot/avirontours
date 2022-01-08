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

use App\Repository\PhysiologyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PhysiologyRepository::class)]
class Physiology
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotNull]
    #[Assert\LessThan(propertyPath: 'heavyAerobicHeartRateMin')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $lightAerobicHeartRateMin = null;

    #[Assert\NotNull]
    #[Assert\LessThan(propertyPath: 'anaerobicThresholdHeartRateMin')]
    #[Assert\GreaterThan(propertyPath: 'lightAerobicHeartRateMin')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $heavyAerobicHeartRateMin = null;

    #[Assert\NotNull]
    #[Assert\LessThan(propertyPath: 'oxygenTransportationHeartRateMin')]
    #[Assert\GreaterThan(propertyPath: 'heavyAerobicHeartRateMin')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $anaerobicThresholdHeartRateMin = null;

    #[Assert\NotNull]
    #[Assert\LessThan(propertyPath: 'anaerobicHeartRateMin')]
    #[Assert\GreaterThan(propertyPath: 'anaerobicThresholdHeartRateMin')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $oxygenTransportationHeartRateMin = null;

    #[Assert\NotNull]
    #[Assert\LessThanOrEqual(propertyPath: 'maximumHeartRate')]
    #[Assert\GreaterThan(propertyPath: 'oxygenTransportationHeartRateMin')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $anaerobicHeartRateMin = null;

    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(value: 100)]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $maximumHeartRate = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $maximumOxygenConsumption = null;

    public function __construct(User $user)
    {
        $user->setPhysiology($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLightAerobicHeartRateMin(): ?int
    {
        return $this->lightAerobicHeartRateMin;
    }

    public function setLightAerobicHeartRateMin(?int $lightAerobicHeartRateMin): self
    {
        $this->lightAerobicHeartRateMin = $lightAerobicHeartRateMin;

        return $this;
    }

    public function getHeavyAerobicHeartRateMin(): ?int
    {
        return $this->heavyAerobicHeartRateMin;
    }

    public function setHeavyAerobicHeartRateMin(?int $heavyAerobicHeartRateMin): self
    {
        $this->heavyAerobicHeartRateMin = $heavyAerobicHeartRateMin;

        return $this;
    }

    public function getAnaerobicThresholdHeartRateMin(): ?int
    {
        return $this->anaerobicThresholdHeartRateMin;
    }

    public function setAnaerobicThresholdHeartRateMin(?int $anaerobicThresholdHeartRateMin): self
    {
        $this->anaerobicThresholdHeartRateMin = $anaerobicThresholdHeartRateMin;

        return $this;
    }

    public function getOxygenTransportationHeartRateMin(): ?int
    {
        return $this->oxygenTransportationHeartRateMin;
    }

    public function setOxygenTransportationHeartRateMin(?int $oxygenTransportationHeartRateMin): self
    {
        $this->oxygenTransportationHeartRateMin = $oxygenTransportationHeartRateMin;

        return $this;
    }

    public function getAnaerobicHeartRateMin(): ?int
    {
        return $this->anaerobicHeartRateMin;
    }

    public function setAnaerobicHeartRateMin(?int $anaerobicHeartRateMin): self
    {
        $this->anaerobicHeartRateMin = $anaerobicHeartRateMin;

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

    public function getLightAerobicHeartRateRange(): ?string
    {
        return $this->getLightAerobicHeartRateMin().' - '.($this->getHeavyAerobicHeartRateMin() - 1);
    }

    public function getHeavyAerobicHeartRateRange(): ?string
    {
        return $this->getHeavyAerobicHeartRateMin().' - '.($this->getAnaerobicThresholdHeartRateMin() - 1);
    }

    public function getAnaerobicThresholdHeartRateRange(): ?string
    {
        return $this->getAnaerobicThresholdHeartRateMin().' - '.($this->getOxygenTransportationHeartRateMin() - 1);
    }

    public function getOxygenTransportationHeartRateRange(): ?string
    {
        return $this->getOxygenTransportationHeartRateMin().' - '.($this->getAnaerobicHeartRateMin() - 1);
    }

    public function getAnaerobicHeartRateRange(): ?string
    {
        return $this->getAnaerobicHeartRateMin().' - '.$this->getMaximumHeartRate();
    }
}
