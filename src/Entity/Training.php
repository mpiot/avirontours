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
    public const NUM_ITEMS = 25;
    public const TYPE_B1 = 'b1';
    public const TYPE_B2 = 'b2';
    public const TYPE_B3 = 'b3';
    public const TYPE_B4 = 'b4';
    public const TYPE_B5 = 'b5';
    public const TYPE_B6 = 'b6';
    public const TYPE_B7 = 'b7';
    public const TYPE_B8 = 'b8';
    public const TYPE_C1 = 'c1';
    public const TYPE_C2 = 'c2';
    public const TYPE_REST = 'rest';
    public const TYPE_GENERALIZED_ENDURANCE = 'generalized_endurance';
    public const TYPE_SPLIT_SHORT = 'split_short';
    public const TYPE_SPLIT_LONG = 'split_long';
    public const ENERGY_PATHWAY_AEROBIC = 'aerobic';
    public const ENERGY_PATHWAY_THRESHOLD = 'threshold';
    public const ENERGY_PATHWAY_LACTIC_ANAEROBIC = 'lactic_aerobic';
    public const ENERGY_PATHWAY_ALACTIC_ANAEROBIC = 'alactic_aerobic';
    public const SPORT_OTHER = 'other';
    public const SPORT_ROWING = 'rowing';
    public const SPORT_RUNNING = 'running';
    public const SPORT_ERGOMETER = 'ergometer';
    public const SPORT_WORKOUT_ENDURANCE = 'workout_endurance';
    public const SPORT_WORKOUT_STRENGTH = 'workout_strength';
    public const SPORT_SWIMMING = 'swimming';
    public const SPORT_GENERAL_PHYSICAL_PREPARATION = 'general_physical_preparation';
    public const SPORT_STRENGTHENING = 'strengthening';
    public const SPORT_CYCLING = 'cycling';
    public const SPORT_YOGA = 'yoga';

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

    #[Assert\LessThanOrEqual(400, message: 'Un entraînement doit faire 400km maximum.')]
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $distance = null;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $sport = null;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $type = null;

    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\LessThanOrEqual(1)]
    #[ORM\Column(type: Types::FLOAT)]
    private ?float $feeling = 0.5;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\OneToMany(mappedBy: 'training', targetEntity: TrainingPhase::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private $trainingPhases;

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

    public function getFormattedDuration(): string
    {
        return DurationManipulator::formatSeconds($this->duration);
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function setDistance(?float $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function getSport(): ?string
    {
        return $this->sport;
    }

    public function getTextSport(): ?string
    {
        return array_flip(self::getAvailableSports())[$this->sport];
    }

    public function setSport(string $sport): self
    {
        $this->sport = $sport;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getTextType(): ?string
    {
        if (null === $this->type) {
            return null;
        }

        return array_flip(self::getAvailableTypes())[$this->type];
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getEnergyPathway(): ?string
    {
        return self::typeToEnergyPathway($this->type);
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

    public function removeTrainingPhase(TrainingPhase $trainingPhase): self
    {
        // set the owning side to null (unless already changed)
        if ($this->trainingPhases->removeElement($trainingPhase) && $trainingPhase->getTraining() === $this) {
            $trainingPhase->setTraining(null);
        }

        return $this;
    }

    public function getPhasesDuration(): ?int
    {
        $duration = 0;
        foreach ($this->getTrainingPhases() as $trainingPhase) {
            $duration += $trainingPhase->getDuration();
        }

        return $duration;
    }

    public function getFormattedPhasesDuration(): string
    {
        return DurationManipulator::formatTenthSeconds($this->getPhasesDuration());
    }

    public function getPhasesDistance(): ?int
    {
        $distance = 0;
        foreach ($this->getTrainingPhases() as $trainingPhase) {
            $distance += $trainingPhase->getDistance();
        }

        return $distance;
    }

    public function getPhasesAveragePace(): ?int
    {
        $paces = [];
        foreach ($this->getTrainingPhases() as $trainingPhase) {
            $paces = array_merge($paces, $trainingPhase->getPaces());
        }

        if (empty($paces)) {
            return null;
        }

        return (int) round(array_sum($paces) / \count($paces), 0);
    }

    public function getPhasesFormattedAveragePace(): ?string
    {
        if (null === $this->getPhasesAveragePace()) {
            return null;
        }

        return DurationManipulator::formatTenthSeconds($this->getPhasesAveragePace());
    }

    public function getPhasesAverageWatt(): ?int
    {
        if (null === $this->getPhasesAveragePace()) {
            return null;
        }

        $paceInSeconds = $this->getPhasesAveragePace() / 10;

        // See https://www.concept2.com/indoor-rowers/training/calculators/watts-calculator
        return (int) round(2.8 / ($paceInSeconds / 500) ** 3, 0);
    }

    public function getPhasesAverageStrokeRate(): ?int
    {
        $strokeRates = [];
        foreach ($this->getTrainingPhases() as $trainingPhase) {
            $strokeRates = array_merge($strokeRates, $trainingPhase->getStrokeRates());
        }

        if (empty($strokeRates)) {
            return null;
        }

        return (int) round(array_sum($strokeRates) / \count($strokeRates), 0);
    }

    public function getPhasesAverageHeartRate(): ?int
    {
        $heartRates = [];
        foreach ($this->getTrainingPhases() as $trainingPhase) {
            $heartRates = array_merge($heartRates, $trainingPhase->getHeartRates() ?? []);
        }

        if (empty($heartRates)) {
            return null;
        }

        return (int) round(array_sum($heartRates) / \count($heartRates), 0);
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

    public static function getAvailableTypes(): array
    {
        return [
            'B1' => self::TYPE_B1,
            'B2' => self::TYPE_B2,
            'B3' => self::TYPE_B3,
            'B4' => self::TYPE_B4,
            'B5' => self::TYPE_B5,
            'B6' => self::TYPE_B6,
            'B7' => self::TYPE_B7,
            'B8' => self::TYPE_B8,
            'C1' => self::TYPE_C1,
            'C2' => self::TYPE_C2,
            'Récupération' => self::TYPE_REST,
            'Endurance généralisée' => self::TYPE_GENERALIZED_ENDURANCE,
            'Fractionné long' => self::TYPE_SPLIT_LONG,
            'Fractionné court' => self::TYPE_SPLIT_SHORT,
        ];
    }

    public static function getAvailableSports(): array
    {
        return [
            'Autre' => self::SPORT_OTHER,
            'Aviron' => self::SPORT_ROWING,
            'Course à pied' => self::SPORT_RUNNING,
            'Ergomètre' => self::SPORT_ERGOMETER,
            'Musculation (Endurance)' => self::SPORT_WORKOUT_ENDURANCE,
            'Musculation (Force)' => self::SPORT_WORKOUT_STRENGTH,
            'Natation' => self::SPORT_SWIMMING,
            'PPG' => self::SPORT_GENERAL_PHYSICAL_PREPARATION,
            'Renforcement' => self::SPORT_STRENGTHENING,
            'Vélo' => self::SPORT_CYCLING,
            'Yoga' => self::SPORT_YOGA,
        ];
    }

    public static function typeToEnergyPathway(?string $type): string
    {
        return match ($type) {
            self::TYPE_B1, self::TYPE_B2, self::TYPE_REST, self::TYPE_GENERALIZED_ENDURANCE => self::ENERGY_PATHWAY_AEROBIC,
            self::TYPE_B3, self::TYPE_B4, self::TYPE_B7, self::TYPE_C2, self::TYPE_SPLIT_LONG => self::ENERGY_PATHWAY_THRESHOLD,
            self::TYPE_B5, self::TYPE_SPLIT_SHORT => self::ENERGY_PATHWAY_LACTIC_ANAEROBIC,
            self::TYPE_B6, self::TYPE_B8, self::TYPE_C1 => self::ENERGY_PATHWAY_ALACTIC_ANAEROBIC,
            default => 'other',
        };
    }

    public static function typeToTextEnergyPathway(?string $type): string
    {
        return match (self::typeToEnergyPathway($type)) {
            self::ENERGY_PATHWAY_AEROBIC => 'Aérobie',
            self::ENERGY_PATHWAY_THRESHOLD => 'Transition aérobie/anaérobie',
            self::ENERGY_PATHWAY_LACTIC_ANAEROBIC => 'Anaérobie lactique',
            self::ENERGY_PATHWAY_ALACTIC_ANAEROBIC => 'Anaérobie alactique',
            default => 'Autre',
        };
    }
}
