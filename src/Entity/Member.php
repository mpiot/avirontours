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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MemberRepository")
 */
class Member
{
    const ROWER_CATEGORY_A = 1;
    const ROWER_CATEGORY_B = 2;
    const ROWER_CATEGORY_C = 3;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $licenseNumber;

    /**
     * @ORM\Column(type="boolean")
     */
    private $licensedToRow;

    /**
     * @ORM\Column(type="date")
     */
    private $licenseEndAt;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\LogbookEntry", mappedBy="crewMembers")
     */
    private $logbookEntries;

    /**
     * @ORM\Column(type="integer")
     */
    private $rowerCategory;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\User", inversedBy="member")
     */
    private $user;

    public function __construct()
    {
        $this->licensedToRow = true;
        $this->licenseEndAt = new \DateTime('last day of october next year');
        $this->logbookEntries = new ArrayCollection();
        $this->rowerCategory = self::ROWER_CATEGORY_C;
    }

    public function __toString()
    {
        return $this->getFullName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->getFirstName().' '.$this->getLastName();
    }

    public function getLicenseNumber(): ?string
    {
        return $this->licenseNumber;
    }

    public function setLicenseNumber(?string $licenseNumber): self
    {
        $this->licenseNumber = $licenseNumber;

        return $this;
    }

    public function getLicensedToRow(): ?bool
    {
        return $this->licensedToRow;
    }

    public function setLicensedToRow(bool $licensedToRow): self
    {
        $this->licensedToRow = $licensedToRow;

        return $this;
    }

    public function getLicenseEndAt(): ?\DateTimeInterface
    {
        return $this->licenseEndAt;
    }

    public function setLicenseEndAt(\DateTimeInterface $licenseEndAt): self
    {
        $this->licenseEndAt = $licenseEndAt;

        return $this;
    }

    public function isLicenseValid(): bool
    {
        $now = (new \DateTime())->setTime(0, 0, 0, 0);

        if ($this->licenseEndAt >= $now) {
            return true;
        }

        return false;
    }

    /**
     * @return Collection|LogbookEntry[]
     */
    public function getLogbookEntries(): Collection
    {
        return $this->logbookEntries;
    }

    public function addLogbookEntry(LogbookEntry $logbookEntry): self
    {
        if (!$this->logbookEntries->contains($logbookEntry)) {
            $this->logbookEntries[] = $logbookEntry;
            $logbookEntry->addCrew($this);
        }

        return $this;
    }

    public function removeLogbookEntry(LogbookEntry $logbookEntry): self
    {
        if ($this->logbookEntries->contains($logbookEntry)) {
            $this->logbookEntries->removeElement($logbookEntry);
            $logbookEntry->removeCrew($this);
        }

        return $this;
    }

    public function getRowerCategory(): ?int
    {
        return $this->rowerCategory;
    }

    public function setRowerCategory(int $rowerCategory): self
    {
        $this->rowerCategory = $rowerCategory;

        return $this;
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
}
