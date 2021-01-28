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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=TrainingPhaseRepository::class)
 */
class TrainingPhase
{
    const INTENSITY_REST = 0;
    const INTENSITY_LIGHT_AEROBIC = 1;
    const INTENSITY_HEAVY_AEROBIC = 2;
    const INTENSITY_ANAEROBIC_THRESHOLD = 3;
    const INTENSITY_OXYGEN_TRANSPORTATION = 4;
    const INTENSITY_ANAEROBIC = 5;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Training::class, inversedBy="trainingPhases")
     * @ORM\JoinColumn(nullable=false)
     */
    private $training;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotNull()
     */
    private $intensity;

    /**
     * @ORM\Column(type="dateinterval")
     * @Assert\NotNull()
     */
    private $duration;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $distance;

    /**
     * @ORM\Column(type="string", length=6, nullable=true)
     * @Assert\Regex(pattern="#\d\:\d{2}\.\d#", message="Le split doit avoir le format: ""0:00.0"".")
     */
    private $split;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $spm;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getIntensity(): ?int
    {
        return $this->intensity;
    }

    public function getTextIntensity(): ?string
    {
        if (false === $key = array_search($this->intensity, self::getAvailableIntensities(), true)) {
            throw new \Exception(sprintf('The intensity "%s" is not available, the method "getAvailableIntensities" only return that intensities: %s.', $this->intensity, implode(', ', self::getAvailableIntensities())));
        }

        return $key;
    }

    public function setIntensity(?int $intensity): self
    {
        $this->intensity = $intensity;

        return $this;
    }

    public function getDuration(): ?\DateInterval
    {
        return $this->duration;
    }

    public function setDuration(?\DateInterval $duration): self
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

    public function getSplit(): ?string
    {
        return $this->split;
    }

    public function setSplit(?string $split): self
    {
        $this->split = $split;

        return $this;
    }

    public function getSpm(): ?int
    {
        return $this->spm;
    }

    public function setSpm(?int $spm): self
    {
        $this->spm = $spm;

        return $this;
    }

    /**
     * @Assert\Callback
     */
    public function validateDuration(ExecutionContextInterface $context)
    {
        if (null === $this->duration) {
            return;
        }

        $date = new \DateTimeImmutable();
        if ($date->add($this->duration) < $date->add(new \DateInterval('PT10S'))) {
            $context->buildViolation('Une phase doit durer au moins 10 secondes.')
                ->atPath('duration')
                ->addViolation();
        }
    }

    public static function getAvailableIntensities(): array
    {
        return [
            'Rest' => self::INTENSITY_REST,
            'B0 - UT2' => self::INTENSITY_LIGHT_AEROBIC,
            'B1 - UT1' => self::INTENSITY_HEAVY_AEROBIC,
            'B2 - AT' => self::INTENSITY_ANAEROBIC_THRESHOLD,
            'B3 - TR' => self::INTENSITY_OXYGEN_TRANSPORTATION,
            'B5 - AN' => self::INTENSITY_ANAEROBIC,
        ];
    }
}
