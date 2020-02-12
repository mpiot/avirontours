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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ShellRepository")
 */
class Shell
{
    const ROWING_TYPE_BOTH = 'both';
    const ROWING_TYPE_SCULL = 'scull';
    const ROWING_TYPE_SWEEP = 'sweep';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Regex("/1|2|4|8/", message="Le nombre de rameur doit être: 1, 2, 4 ou 8.")
     */
    private $numberRowers;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull()
     */
    private $coxed;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull()
     */
    private $rowingType;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull()
     */
    private $yolette;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull()
     */
    private $enabled;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $abbreviation;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\LogbookEntry", mappedBy="shell")
     */
    private $logbookEntries;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $productionYear;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $weightCategory;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $newPrice;

    /**
     * @ORM\Column(type="float")
     * @Assert\NotBlank()
     * @Assert\GreaterThanOrEqual(0)
     */
    private $mileage;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $riggerMaterial;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $riggerPosition;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $usageFrequency;

    /**
     * @ORM\Column(type="integer")
     */
    private $rowerCategory;

    /**
     * @ORM\Column(type="boolean")
     */
    private $personalBoat;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ShellDamage", mappedBy="shell")
     */
    private $shellDamages;

    public function __construct()
    {
        $this->coxed = false;
        $this->yolette = false;
        $this->enabled = true;
        $this->logbookEntries = new ArrayCollection();
        $this->personalBoat = false;
        $this->shellDamages = new ArrayCollection();
        $this->rowerCategory = Member::ROWER_CATEGORY_C;
        $this->rowingType = self::ROWING_TYPE_BOTH;
        $this->mileage = 0;
    }

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

    public function getNumberRowers(): ?int
    {
        return $this->numberRowers;
    }

    public function setNumberRowers(int $numberRowers): self
    {
        $this->numberRowers = $numberRowers;

        return $this;
    }

    public function getCoxed(): ?bool
    {
        return $this->coxed;
    }

    public function setCoxed(bool $coxed): self
    {
        $this->coxed = $coxed;

        return $this;
    }

    public function getRowingType(): ?string
    {
        return $this->rowingType;
    }

    public function getStringRowingType(): string
    {
        switch ($this->rowingType) {
            case self::ROWING_TYPE_BOTH:
                return 'Les deux';
                break;

            case self::ROWING_TYPE_SCULL:
                return 'Couple';
                break;

            case self::ROWING_TYPE_SWEEP:
                return 'Pointe';
                break;
        }

        return '';
    }

    public function setRowingType(string $rowingType): self
    {
        $this->rowingType = $rowingType;

        return $this;
    }

    public function getYolette(): ?bool
    {
        return $this->yolette;
    }

    public function setYolette(bool $yolette): self
    {
        $this->yolette = $yolette;

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }

    public function setAbbreviation(string $abbreviation): self
    {
        $this->abbreviation = $abbreviation;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->getName().' ('.$this->getAbbreviation().')';
    }

    public function getCrewSize(): int
    {
        $crewSize = $this->numberRowers;

        if (true === $this->coxed) {
            ++$crewSize;
        }

        return $crewSize;
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
            $logbookEntry->setShell($this);
        }

        return $this;
    }

    public function removeLogbookEntry(LogbookEntry $logbookEntry): self
    {
        if ($this->logbookEntries->contains($logbookEntry)) {
            $this->logbookEntries->removeElement($logbookEntry);
            // set the owning side to null (unless already changed)
            if ($logbookEntry->getShell() === $this) {
                $logbookEntry->setShell(null);
            }
        }

        return $this;
    }

    public function getProductionYear(): ?int
    {
        return $this->productionYear;
    }

    public function setProductionYear(?int $productionYear): self
    {
        $this->productionYear = $productionYear;

        return $this;
    }

    public function getWeightCategory(): ?string
    {
        return $this->weightCategory;
    }

    public function setWeightCategory(?string $weightCategory): self
    {
        $this->weightCategory = $weightCategory;

        return $this;
    }

    public function getNewPrice(): ?float
    {
        return $this->newPrice;
    }

    public function setNewPrice(?float $newPrice): self
    {
        $this->newPrice = $newPrice;

        return $this;
    }

    public function getMileage(): ?float
    {
        return $this->mileage;
    }

    public function setMileage(?float $mileage): self
    {
        $this->mileage = $mileage;

        return $this;
    }

    public function addToMileage(float $coveredDistance): self
    {
        $this->mileage += $coveredDistance;

        return $this;
    }

    public function removeToMileage(float $coveredDistance): self
    {
        $this->mileage -= $coveredDistance;

        return $this;
    }

    public function getRiggerMaterial(): ?string
    {
        return $this->riggerMaterial;
    }

    public function setRiggerMaterial(?string $riggerMaterial): self
    {
        $this->riggerMaterial = $riggerMaterial;

        return $this;
    }

    public function getRiggerPosition(): ?string
    {
        return $this->riggerPosition;
    }

    public function setRiggerPosition(?string $riggerPosition): self
    {
        $this->riggerPosition = $riggerPosition;

        return $this;
    }

    public function getUsageFrequency(): ?int
    {
        return $this->usageFrequency;
    }

    public function setUsageFrequency(?int $usageFrequency): self
    {
        $this->usageFrequency = $usageFrequency;

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

    public function getPersonalBoat(): ?bool
    {
        return $this->personalBoat;
    }

    public function setPersonalBoat(bool $personalBoat): self
    {
        $this->personalBoat = $personalBoat;

        return $this;
    }

    /**
     * @return Collection|ShellDamage[]
     */
    public function getShellDamages(): Collection
    {
        return $this->shellDamages;
    }

    public function addShellDamage(ShellDamage $shellDamage): self
    {
        if (!$this->shellDamages->contains($shellDamage)) {
            $this->shellDamages[] = $shellDamage;
            $shellDamage->setShell($this);
        }

        return $this;
    }

    public function removeShellDamage(ShellDamage $shellDamage): self
    {
        if ($this->shellDamages->contains($shellDamage)) {
            $this->shellDamages->removeElement($shellDamage);
            // set the owning side to null (unless already changed)
            if ($shellDamage->getShell() === $this) {
                $shellDamage->setShell(null);
            }
        }

        return $this;
    }
}
