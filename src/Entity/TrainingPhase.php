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

use App\Repository\TrainingPhaseRepository;
use App\Util\DurationManipulator;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrainingPhaseRepository::class)]
class TrainingPhase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Training::class, inversedBy: 'trainingPhases')]
    #[ORM\JoinColumn(nullable: false)]
    private $training;

    #[ORM\Column(type: Types::INTEGER)]
    private $duration;

    #[ORM\Column(type: Types::INTEGER)]
    private $distance;

    #[ORM\Column(type: 'integer[]')]
    private ?array $times = null;

    #[ORM\Column(type: 'integer[]')]
    private ?array $distances = null;

    #[ORM\Column(type: 'integer[]')]
    private ?array $paces = null;

    #[ORM\Column(type: 'integer[]')]
    private ?array $strokeRates = null;

    #[ORM\Column(type: 'integer[]', nullable: true)]
    private ?array $heartRates = null;

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
        return DurationManipulator::formatTenthSeconds($this->duration);
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

    public function getAveragePace(): ?int
    {
        return (int) round(array_sum($this->paces) / \count($this->paces), 0);
    }

    public function getFormattedAveragePace(): string
    {
        return DurationManipulator::formatTenthSeconds($this->getAveragePace());
    }

    public function getAverageWatt(): ?int
    {
        $paceInSeconds = $this->getAveragePace() / 10;

        // See https://www.concept2.com/indoor-rowers/training/calculators/watts-calculator
        return (int) round(2.8 / ($paceInSeconds / 500) ** 3, 0);
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

    public function getAverageStrokeRate(): ?int
    {
        return (int) round(array_sum($this->strokeRates) / \count($this->strokeRates), 0);
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

    public function getAverageHeartRate(): ?int
    {
        if (null === $this->heartRates) {
            return null;
        }

        return (int) round(array_sum($this->heartRates) / \count($this->heartRates), 0);
    }

    public function setHeartRates(?array $heartRates): self
    {
        $this->heartRates = $heartRates;

        return $this;
    }
}
