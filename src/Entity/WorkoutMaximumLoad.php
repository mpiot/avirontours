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

use App\Repository\WorkoutMaximumLoadRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=WorkoutMaximumLoadRepository::class)
 */
class WorkoutMaximumLoad
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero
     */
    private $rowingTirage;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero
     */
    private $benchPress;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero
     */
    private $squat;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero
     */
    private $legPress;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero
     */
    private $clean;

    public function __construct(User $user)
    {
        $user->setWorkoutMaximumLoad($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRowingTirage(): ?int
    {
        return $this->rowingTirage;
    }

    public function setRowingTirage(int $rowingTirage): self
    {
        $this->rowingTirage = $rowingTirage;

        return $this;
    }

    public function getBenchPress(): ?int
    {
        return $this->benchPress;
    }

    public function setBenchPress(int $benchPress): self
    {
        $this->benchPress = $benchPress;

        return $this;
    }

    public function getSquat(): ?int
    {
        return $this->squat;
    }

    public function setSquat(int $squat): self
    {
        $this->squat = $squat;

        return $this;
    }

    public function getLegPress(): ?int
    {
        return $this->legPress;
    }

    public function setLegPress(int $legPress): self
    {
        $this->legPress = $legPress;

        return $this;
    }

    public function getClean(): ?int
    {
        return $this->clean;
    }

    public function setClean(int $clean): self
    {
        $this->clean = $clean;

        return $this;
    }
}
