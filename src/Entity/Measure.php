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

use App\Enum\MeasureType;
use App\Repository\MeasureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(['user', 'measuredAt', 'type'], errorPath: 'measuredAt')]
#[ORM\Entity(repositoryClass: MeasureRepository::class)]
class Measure
{
    public const int NUM_ITEMS = 25;

    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'measures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $measuredAt;

    #[Assert\NotNull]
    #[ORM\Column(enumType: MeasureType::class)]
    private ?MeasureType $type = null;

    #[Assert\NotNull]
    #[Assert\GreaterThan(0)]
    #[ORM\Column]
    private ?float $value = null;

    public function __construct()
    {
        $this->measuredAt = new \DateTimeImmutable('today');
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

    public function getMeasuredAt(): ?\DateTimeImmutable
    {
        return $this->measuredAt;
    }

    public function setMeasuredAt(?\DateTimeImmutable $measuredAt): static
    {
        $this->measuredAt = $measuredAt;

        return $this;
    }

    public function getType(): ?MeasureType
    {
        return $this->type;
    }

    public function setType(?MeasureType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(?float $value): static
    {
        $this->value = $value;

        return $this;
    }
}
