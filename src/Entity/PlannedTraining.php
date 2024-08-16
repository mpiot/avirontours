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

use App\Enum\DayPart;
use App\Enum\EnergyPathwayType;
use App\Enum\SportType;
use App\Enum\TrainingType;
use App\Repository\PlannedTrainingRepository;
use App\Util\DurationManipulator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PlannedTrainingRepository::class)]
class PlannedTraining
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'plannedTrainings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TrainingPlan $trainingPlan = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'plannedTrainings')]
    private Collection $followers;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date;

    #[ORM\Column(nullable: true)]
    private ?DayPart $dayPart = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $duration = null;

    #[Assert\LessThanOrEqual(400000, message: 'Un entraînement doit faire 400km maximum.')]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $distance = null;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::STRING, length: 255, enumType: SportType::class)]
    private ?SportType $sport = null;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, enumType: TrainingType::class)]
    private ?TrainingType $type = null;

    #[ORM\Column(nullable: true)]
    private ?int $ratedPerceivedExertion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    public function __construct(TrainingPlan $trainingPlan, \DateTimeImmutable $date)
    {
        $this->trainingPlan = $trainingPlan;
        $this->date = $date;
        $this->followers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrainingPlan(): ?TrainingPlan
    {
        return $this->trainingPlan;
    }

    public function setTrainingPlan(?TrainingPlan $trainingPlan): static
    {
        $this->trainingPlan = $trainingPlan;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(?\DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getDayPart(): ?DayPart
    {
        return $this->dayPart;
    }

    public function setDayPart(?DayPart $dayPart): self
    {
        $this->dayPart = $dayPart;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function getFormattedDuration(bool $displayTenth = false, string $hoursSeparator = ':'): ?string
    {
        if (null === $this->duration) {
            return null;
        }

        if (true === $displayTenth) {
            return DurationManipulator::formatTenthSeconds($this->duration);
        }

        $seconds = (int) round($this->duration / 10);

        return DurationManipulator::formatSeconds($seconds, $hoursSeparator);
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getDistance(bool $inKilometers = false): ?int
    {
        if (true === $inKilometers) {
            return $this->distance / 1000;
        }

        return $this->distance;
    }

    public function setDistance(?int $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function getSport(): ?SportType
    {
        return $this->sport;
    }

    public function setSport(SportType $sport): self
    {
        $this->sport = $sport;

        return $this;
    }

    public function getType(): ?TrainingType
    {
        return $this->type;
    }

    public function setType(?TrainingType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getEnergyPathway(): EnergyPathwayType
    {
        return EnergyPathwayType::fromTrainingType($this->type);
    }

    public function getRatedPerceivedExertion(): ?int
    {
        return $this->ratedPerceivedExertion;
    }

    public function setRatedPerceivedExertion(?int $ratedPerceivedExertion): static
    {
        $this->ratedPerceivedExertion = $ratedPerceivedExertion;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
