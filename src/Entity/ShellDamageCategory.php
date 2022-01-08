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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'App\Repository\ShellDamageCategoryRepository')]
class ShellDamageCategory
{
    public const PRIORITY_HIGH = 0;
    public const PRIORITY_MEDIUM = 1;

    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $priority = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function getTextPriority(): ?string
    {
        return array_flip(self::getAvailablePriorities())[$this->priority];
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public static function getAvailablePriorities(): array
    {
        return [
            'Intermédiaire' => self::PRIORITY_MEDIUM,
            'Importante' => self::PRIORITY_HIGH,
        ];
    }
}
