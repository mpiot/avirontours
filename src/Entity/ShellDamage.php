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

use App\Entity\Traits\TimestampableEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ShellDamageRepository")
 */
class ShellDamage
{
    use TimestampableEntity;
    public const NUM_ITEMS = 20;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ShellDamageCategory")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull
     */
    private ?ShellDamageCategory $category = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $description = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $note = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $repairStartAt = null;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private ?\DateTimeInterface $repairEndAt = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Shell", inversedBy="shellDamages")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull
     */
    private ?Shell $shell = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogbookEntry", inversedBy="shellDamages")
     */
    private ?LogbookEntry $logbookEntry = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?ShellDamageCategory
    {
        return $this->category;
    }

    public function setCategory(ShellDamageCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getRepairStartAt(): ?\DateTimeInterface
    {
        return $this->repairStartAt;
    }

    public function setRepairStartAt(?\DateTimeInterface $repairStartAt): self
    {
        $this->repairStartAt = $repairStartAt;

        return $this;
    }

    public function getRepairEndAt(): ?\DateTimeInterface
    {
        return $this->repairEndAt;
    }

    public function setRepairEndAt(?\DateTimeInterface $repairEndAt): self
    {
        $this->repairEndAt = $repairEndAt;

        return $this;
    }

    public function getShell(): ?Shell
    {
        return $this->shell;
    }

    public function setShell(?Shell $shell): self
    {
        $this->shell = $shell;

        return $this;
    }

    public function getLogbookEntry(): ?LogbookEntry
    {
        return $this->logbookEntry;
    }

    public function setLogbookEntry(?LogbookEntry $logbookEntry): self
    {
        $this->logbookEntry = $logbookEntry;

        return $this;
    }
}
