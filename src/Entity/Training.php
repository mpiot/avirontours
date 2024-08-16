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

use App\Enum\EnergyPathwayType;
use App\Enum\SportType;
use App\Enum\TrainingType;
use App\Repository\TrainingRepository;
use App\Util\DurationManipulator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: TrainingRepository::class)]
class Training
{
    public const int NUM_ITEMS = 25;

    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'trainings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $trainedAt;

    #[ORM\Column(type: Types::INTEGER)]
    private int $duration = 0;

    #[Assert\LessThanOrEqual(400000, message: 'Un entraînement doit faire 400km maximum.')]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $distance = null;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::STRING, length: 255, enumType: SportType::class)]
    private ?SportType $sport = null;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, enumType: TrainingType::class)]
    private ?TrainingType $type = null;

    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\LessThanOrEqual(1)]
    #[ORM\Column(type: Types::FLOAT)]
    private ?float $feeling = 0.75;

    #[ORM\Column(nullable: true)]
    private ?int $ratedPerceivedExertion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\OneToMany(mappedBy: 'training', targetEntity: TrainingPhase::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $trainingPhases;

    #[ORM\Column(nullable: true)]
    private ?int $strokeRate = null;

    #[ORM\Column(nullable: true)]
    private ?int $averageHeartRate = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxHeartRate = null;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->trainedAt = new \DateTime((new \DateTime())->format('Y-m-d H:i'));
        $this->trainingPhases = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTrainedAt(): ?\DateTime
    {
        return $this->trainedAt;
    }

    public function setTrainedAt(?\DateTime $trainedAt): self
    {
        $this->trainedAt = $trainedAt;

        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getFormattedDuration(bool $displayTenth = false): string
    {
        if (true === $displayTenth) {
            return DurationManipulator::formatTenthSeconds($this->duration);
        }

        $seconds = (int) round($this->duration / 10);

        return DurationManipulator::formatSeconds($seconds);
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

    public function setDistance(?int $distance): self
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
        return DurationManipulator::formatTenthSeconds($this->getPace());
    }

    public function getAverageWatt(): ?int
    {
        $paceInSeconds = $this->getPace() / 10;

        // See https://www.concept2.com/indoor-rowers/training/calculators/watts-calculator
        return (int) round(2.8 / ($paceInSeconds / 500) ** 3);
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

    public function getFeeling(): ?float
    {
        return $this->feeling;
    }

    public function setFeeling(?float $feeling): self
    {
        $this->feeling = $feeling;

        return $this;
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

    /**
     * @return Collection<int, TrainingPhase>
     */
    public function getTrainingPhases(): Collection
    {
        return $this->trainingPhases;
    }

    public function addTrainingPhase(TrainingPhase $trainingPhase): self
    {
        if (!$this->trainingPhases->contains($trainingPhase)) {
            $this->trainingPhases[] = $trainingPhase;
            $trainingPhase->setTraining($this);
        }

        return $this;
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

    public function getMaxHeartRate(): ?int
    {
        return $this->maxHeartRate;
    }

    public function setMaxHeartRate(?int $maxHeartRate): static
    {
        $this->maxHeartRate = $maxHeartRate;

        return $this;
    }

    #[Assert\Callback]
    public function validateDuration(ExecutionContextInterface $context): void
    {
        if ($this->getDuration() < 5 * 60) {
            $context->buildViolation('Un entraînement doit durer au moins 5 minutes.')
                ->atPath('duration')
                ->addViolation()
            ;
        }
    }
}
