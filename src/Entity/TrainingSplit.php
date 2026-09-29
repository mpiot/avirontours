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

use App\Repository\TrainingSplitRepository;
use App\Util\DurationManipulator;
use App\Util\WattCalculator;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A per-segment summary of a continuous workout, mirroring a Concept2 `workout.splits` entry.
 */
#[ORM\Entity(repositoryClass: TrainingSplitRepository::class)]
class TrainingSplit
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Training::class, inversedBy: 'trainingSplits')]
    #[ORM\JoinColumn(nullable: false)]
    private Training $training;

    #[ORM\Column(type: Types::INTEGER)]
    private int $duration;

    #[ORM\Column(type: Types::INTEGER)]
    private int $distance;

    #[ORM\Column(nullable: true)]
    private ?int $strokeRate = null;

    #[ORM\Column(nullable: true)]
    private ?int $averageHeartRate = null;

    #[ORM\Column(nullable: true)]
    private ?int $endingHeartRate = null;

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

    public function getPace(): ?int
    {
        return (int) round(500 * ($this->duration / $this->distance));
    }

    public function getFormattedPace(): string
    {
        return DurationManipulator::formatTenthSecondsAsHoursMinutesSecondsAndTenthSeconds($this->getPace());
    }

    public function getAverageWatt(): ?int
    {
        return WattCalculator::calculateFromPace($this->getPace());
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

    public function getAverageHeartRate(): ?int
    {
        return $this->averageHeartRate;
    }

    public function setAverageHeartRate(?int $averageHeartRate): static
    {
        $this->averageHeartRate = $averageHeartRate;

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
}
