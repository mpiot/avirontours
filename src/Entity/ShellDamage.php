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

use App\Entity\Traits\TimestampableEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ShellDamageRepository")
 */
class ShellDamage
{
    use TimestampableEntity;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ShellDamageCategory")
     * @ORM\JoinColumn(nullable=false)
     */
    private $category;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $doneAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Shell", inversedBy="shellDamages")
     * @ORM\JoinColumn(nullable=false)
     */
    private $shell;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogbookEntry", inversedBy="shellDamages")
     */
    private $logbookEntry;

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

    public function getDoneAt(): ?\DateTimeInterface
    {
        return $this->doneAt;
    }

    public function setDoneAt(?\DateTimeInterface $doneAt): self
    {
        $this->doneAt = $doneAt;

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
