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

use App\Repository\TrainingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=TrainingRepository::class)
 */
class Training
{
    const NUM_ITEMS = 25;

    const FEELING_GREAT = 0.2;
    const FEELING_GOOD = 0.4;
    const FEELING_OK = 0.6;
    const FEELING_NOT_GOOD = 0.8;
    const FEELING_BAD = 1.0;

    const SPORT_OTHER = 'other';
    const SPORT_ROWING = 'rowing';
    const SPORT_RUNNING = 'running';
    const SPORT_ERGOMETER = 'ergometer';
    const SPORT_WORKOUT_ENDURANCE = 'workout_endurance';
    const SPORT_WORKOUT_STRENGTH = 'workout_strength';
    const SPORT_SWIMMING = 'swimming';
    const SPORT_GENERAL_PHYSICAL_PREPARATION = 'general_physical_preparation';
    const SPORT_STRENGTHENING = 'strengthening';
    const SPORT_CYCLING = 'cycling';
    const SPORT_YOGA = 'yoga';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="trainings")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotNull()
     */
    private $trained_at;

    /**
     * @ORM\Column(type="time")
     * @Assert\NotNull()
     */
    private $duration;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $distance;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull()
     */
    private $sport;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $feeling;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    /**
     * @ORM\OneToMany(targetEntity=TrainingPhase::class, mappedBy="training", orphanRemoval=true, cascade={"persist", "remove"})
     * @ORM\OrderBy({"id": "ASC"})
     * @Assert\Valid()
     */
    private $trainingPhases;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->trained_at = new \DateTime('-1 hour');
        $this->duration = new \DateTime('01:00:00');
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

    public function getTrainedAt(): ?\DateTimeInterface
    {
        return $this->trained_at;
    }

    public function setTrainedAt(?\DateTimeInterface $trained_at): self
    {
        $this->trained_at = $trained_at;

        return $this;
    }

    public function getDuration(): ?\DateTime
    {
        return $this->duration;
    }

    public function setDuration(?\DateTime $duration): self
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
        $availableSports = array_flip(self::getAvailableSports());
        if (!\array_key_exists($this->sport, $availableSports)) {
            throw new \Exception(sprintf('The sport "%s" is not available, the method "getAvailableSports" only return that sports: %s.', $this->sport, implode(', ', self::getAvailableSports())));
        }

        return $availableSports[$this->sport];
    }

    public function setSport(string $sport): self
    {
        $this->sport = $sport;

        return $this;
    }

    public function getFeeling(): ?float
    {
        return $this->feeling;
    }

    public function getTextFeeling(): ?string
    {
        if (null === $this->feeling) {
            return null;
        }

        if (false === $key = array_search($this->feeling, self::getAvailableFeelings(), true)) {
            throw new \Exception(sprintf('The feeling "%s" is not available, the method "getAvailableFeelings" only return that feelings: %s.', $this->feeling, implode(', ', self::getAvailableFeelings())));
        }

        return $key;
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
     * @return Collection|TrainingPhase[]
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
        if ($this->trainingPhases->removeElement($trainingPhase)) {
            // set the owning side to null (unless already changed)
            if ($trainingPhase->getTraining() === $this) {
                $trainingPhase->setTraining(null);
            }
        }

        return $this;
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

    public static function getAvailableFeelings(): array
    {
        return [
            'Super bien !' => self::FEELING_GREAT,
            'Bien' => self::FEELING_GOOD,
            'OK' => self::FEELING_OK,
            'Pas bien' => self::FEELING_NOT_GOOD,
            'Mal' => self::FEELING_BAD,
        ];
    }
}
