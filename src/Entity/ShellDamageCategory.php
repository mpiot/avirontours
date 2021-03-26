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

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ShellDamageCategoryRepository")
 */
class ShellDamageCategory
{
    public const PRIORITY_HIGH = 0;
    public const PRIORITY_MEDIUM = 1;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     */
    private ?string $name = null;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotNull
     */
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
        $availablePriorities = array_flip(self::getAvailablePriorities());
        if (!\array_key_exists($this->priority, $availablePriorities)) {
            throw new \Exception(sprintf('The priority "%s" is not available, the method "getAvailablePriorities" only return that priorities: %s.', $this->priority, implode(', ', self::getAvailablePriorities())));
        }

        return $availablePriorities[$this->priority];
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
