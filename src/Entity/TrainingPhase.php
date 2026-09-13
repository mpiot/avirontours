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

use App\Enum\TrainingPhaseIntensity;
use App\Repository\TrainingPhaseRepository;
use App\Util\DurationManipulator;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrainingPhaseRepository::class)]
class TrainingPhase
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Training::class, inversedBy: 'trainingPhases')]
    #[ORM\JoinColumn(nullable: false)]
    private Training $training;

    #[ORM\Column(type: Types::INTEGER)]
    private int $duration;

    #[ORM\Column(type: Types::INTEGER)]
    private int $distance;

    #[ORM\Column(type: 'integer[]', nullable: true)]
    private ?array $times = null;

    #[ORM\Column(type: 'integer[]', nullable: true)]
    private ?array $distances = null;

    #[ORM\Column(type: 'integer[]', nullable: true)]
    private ?array $paces = null;

    #[ORM\Column(type: 'integer[]', nullable: true)]
    private ?array $strokeRates = null;

    #[ORM\Column(nullable: true)]
    private ?int $strokeRate = null;

    #[ORM\Column(type: 'integer[]', nullable: true)]
    private ?array $heartRates = null;

    #[ORM\Column(nullable: true)]
    private ?int $averageHeartRate = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxHeartRate = null;

    #[ORM\Column(nullable: true)]
    private ?int $endingHeartRate = null;

    #[ORM\Column(nullable: true, enumType: TrainingPhaseIntensity::class)]
    private ?TrainingPhaseIntensity $intensity = null;

    #[ORM\Column(type: 'integer[]', nullable: true)]
    private ?array $powers = null;

    #[ORM\Column(nullable: true)]
    private ?int $averagePower = null;

    // GPS track: $positionTimes holds their $times value, so a signal loss is a gap between two consecutive entries.
    // Positions are raw FIT semicircles, altitudes metres.
    #[ORM\Column(type: 'integer[]', nullable: true)]
    private ?array $positionTimes = null;

    #[ORM\Column(type: 'integer[]', nullable: true)]
    private ?array $latitudes = null;

    #[ORM\Column(type: 'integer[]', nullable: true)]
    private ?array $longitudes = null;

    #[ORM\Column(type: 'integer[]', nullable: true)]
    private ?array $altitudes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTraining(): ?Training
    {
        return $this->training;
    }

    public function setTraining(?Training $training): self
    {
        $this->training = $training;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function getFormattedDuration(): string
    {
        return DurationManipulator::formatTenthSecondsAsHoursMinutesSecondsAndTenthSeconds($this->duration);
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getDistance(): ?int
    {
        return $this->distance;
    }

    public function setDistance(int $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function getTimes(): ?array
    {
        return $this->times;
    }

    public function setTimes(?array $times): self
    {
        $this->times = $times;

        return $this;
    }

    public function getDistances(): ?array
    {
        return $this->distances;
    }

    public function setDistances(?array $distances): self
    {
        $this->distances = $distances;

        return $this;
    }

    public function getPaces(): ?array
    {
        return $this->paces;
    }

    public function getPace(): ?int
    {
        if (0 === $this->distance) {
            return null;
        }

        return (int) round(500 * ($this->duration / $this->distance));
    }

    public function getFormattedPace(): ?string
    {
        $pace = $this->getPace();
        if (null === $pace) {
            return null;
        }

        return DurationManipulator::formatTenthSecondsAsHoursMinutesSecondsAndTenthSeconds($pace);
    }

    public function getAverageWatt(): ?int
    {
        if (null !== $this->averagePower) {
            return $this->averagePower;
        }

        $pace = $this->getPace();
        if (null === $pace || true !== $this->training->getSport()?->tracksWatts()) {
            return null;
        }

        $paceInSeconds = $pace / 10;

        // See https://www.concept2.com/indoor-rowers/training/calculators/watts-calculator
        return (int) round(2.8 / ($paceInSeconds / 500) ** 3);
    }

    public function setPaces(?array $paces): self
    {
        $this->paces = $paces;

        return $this;
    }

    public function getStrokeRates(): ?array
    {
        return $this->strokeRates;
    }

    public function getStrokeRate(): ?int
    {
        return $this->strokeRate;
    }

    public function setStrokeRate(?int $strokeRate): static
    {
        $this->strokeRate = $strokeRate;

        return $this;
    }

    public function setStrokeRates(?array $strokeRates): self
    {
        $this->strokeRates = $strokeRates;

        return $this;
    }

    public function getHeartRates(): ?array
    {
        return $this->heartRates;
    }

    public function setHeartRates(?array $heartRates): self
    {
        $this->heartRates = $heartRates;

        return $this;
    }

    public function getAverageHeartRate(): ?int
    {
        return $this->averageHeartRate;
    }

    public function setAverageHeartRate(?int $averageHeartRate): static
    {
        $this->averageHeartRate = $averageHeartRate;

        return $this;
    }

    public function getMaxHeartRate(): ?int
    {
        return $this->maxHeartRate;
    }

    public function setMaxHeartRate(?int $maxHeartRate): static
    {
        $this->maxHeartRate = $maxHeartRate;

        return $this;
    }

    public function getEndingHeartRate(): ?int
    {
        return $this->endingHeartRate;
    }

    public function setEndingHeartRate(?int $endingHeartRate): static
    {
        $this->endingHeartRate = $endingHeartRate;

        return $this;
    }

    public function getIntensity(): ?TrainingPhaseIntensity
    {
        return $this->intensity;
    }

    public function setIntensity(?TrainingPhaseIntensity $intensity): static
    {
        $this->intensity = $intensity;

        return $this;
    }

    public function isRest(): bool
    {
        return TrainingPhaseIntensity::Rest === $this->intensity;
    }

    public function getPowers(): ?array
    {
        return $this->powers;
    }

    public function setPowers(?array $powers): static
    {
        $this->powers = $powers;

        return $this;
    }

    public function getAveragePower(): ?int
    {
        return $this->averagePower;
    }

    public function setAveragePower(?int $averagePower): static
    {
        $this->averagePower = $averagePower;

        return $this;
    }

    public function getPositionTimes(): ?array
    {
        return $this->positionTimes;
    }

    public function setPositionTimes(?array $positionTimes): static
    {
        $this->positionTimes = $positionTimes;

        return $this;
    }

    public function getLatitudes(): ?array
    {
        return $this->latitudes;
    }

    public function setLatitudes(?array $latitudes): static
    {
        $this->latitudes = $latitudes;

        return $this;
    }

    public function getLongitudes(): ?array
    {
        return $this->longitudes;
    }

    public function setLongitudes(?array $longitudes): static
    {
        $this->longitudes = $longitudes;

        return $this;
    }

    public function getAltitudes(): ?array
    {
        return $this->altitudes;
    }

    public function setAltitudes(?array $altitudes): static
    {
        $this->altitudes = $altitudes;

        return $this;
    }
}
