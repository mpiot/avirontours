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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ShellRepository")
 */
class Shell
{
    public const ROWER_CATEGORY_A = 1;
    public const ROWER_CATEGORY_B = 2;
    public const ROWER_CATEGORY_C = 3;

    public const ROWING_TYPE_BOTH = 'both';
    public const ROWING_TYPE_SCULL = 'scull';
    public const ROWING_TYPE_SWEEP = 'sweep';

    public const WEIGHT_CATEGORY_50 = 50;
    public const WEIGHT_CATEGORY_60 = 60;
    public const WEIGHT_CATEGORY_70 = 70;
    public const WEIGHT_CATEGORY_80 = 80;
    public const WEIGHT_CATEGORY_90 = 90;

    public const RIGGER_MATERIAL_ALUMINIUM = 'aluminum';
    public const RIGGER_MATERIAL_CARBON = 'carbon';

    public const RIGGER_POSITION_BACK = 'back';
    public const RIGGER_POSITION_FRONT = 'front';

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
     * @Assert\Regex("/1|2|4|8/", message="Le nombre de rameur doit être: 1, 2, 4 ou 8.")
     * @Assert\NotBlank
     */
    private ?int $numberRowers = null;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull
     */
    private ?bool $coxed = null;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull
     */
    private ?string $rowingType = null;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull
     */
    private ?bool $yolette = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $abbreviation = null;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\LogbookEntry", mappedBy="shell")
     */
    private Collection $logbookEntries;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $productionYear = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $weightCategory = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $newPrice = null;

    /**
     * @ORM\Column(type="float")
     * @Assert\NotBlank
     * @Assert\GreaterThanOrEqual(0)
     */
    private $mileage;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $riggerMaterial = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $riggerPosition = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $usageFrequency = null;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $rowerCategory = null;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $personalBoat = null;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ShellDamage", mappedBy="shell")
     */
    private Collection $shellDamages;

    public function __construct()
    {
        $this->coxed = false;
        $this->yolette = false;
        $this->logbookEntries = new ArrayCollection();
        $this->personalBoat = false;
        $this->shellDamages = new ArrayCollection();
        $this->rowerCategory = User::ROWER_CATEGORY_C;
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

    public function getTextRowingType(): string
    {
        $availableRowingTypes = array_flip(self::getAvailableRowingTypes());
        if (!\array_key_exists($this->rowingType, $availableRowingTypes)) {
            throw new \Exception(sprintf('The rowingType "%s" is not available, the method "getAvailableRowingTypes" only return that rowingTypes: %s.', $this->rowingType, implode(', ', self::getAvailableRowingTypes())));
        }

        return $availableRowingTypes[$this->rowingType];
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

    public function getTextWeightCategory($withUnit = true): ?string
    {
        $availableWeightCategories = array_flip(self::getAvailableWeightCategories());
        if (!\array_key_exists($this->weightCategory, $availableWeightCategories)) {
            throw new \Exception(sprintf('The weightCategory "%s" is not available, the method "getAvailableWeightCategories" only return that weightCategories: %s.', $this->weightCategory, implode(', ', self::getAvailableWeightCategories())));
        }

        $text = $availableWeightCategories[$this->weightCategory];

        if (true === $withUnit) {
            $text .= ' Kg';
        }

        return $text;
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
        return round($this->mileage, 1);
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

    public function getTextRiggerMaterial(): ?string
    {
        $availableRiggerMaterials = array_flip(self::getAvailableRiggerMaterials());
        if (!\array_key_exists($this->riggerMaterial, $availableRiggerMaterials)) {
            throw new \Exception(sprintf('The riggerMaterial "%s" is not available, the method "getAvailableRiggerMaterials" only return that riggerMaterials: %s.', $this->riggerMaterial, implode(', ', self::getAvailableGenders())));
        }

        return $availableRiggerMaterials[$this->riggerMaterial];
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

    public function getTextRiggerPosition(): ?string
    {
        $availableRiggerPositions = array_flip(self::getAvailableRiggerPositions());
        if (!\array_key_exists($this->riggerPosition, $availableRiggerPositions)) {
            throw new \Exception(sprintf('The riggerPosition "%s" is not available, the method "getAvailableRiggerPositions" only return that riggerPositions: %s.', $this->riggerPosition, implode(', ', self::getAvailableRiggerPositions())));
        }

        return $availableRiggerPositions[$this->riggerPosition];
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

    public function getTextRowerCategory(): string
    {
        $availableRowerCategories = array_flip(self::getAvailableRowerCategories());
        if (!\array_key_exists($this->rowerCategory, $availableRowerCategories)) {
            throw new \Exception(sprintf('The rowerCategory "%s" is not available, the method "getAvailableRowerCategories" only return that categories: %s.', $this->rowerCategory, implode(', ', self::getAvailableRowerCategories())));
        }

        return $availableRowerCategories[$this->rowerCategory];
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

    public static function getAvailableRiggerMaterials(): array
    {
        return [
            'Aluminium' => self::RIGGER_MATERIAL_ALUMINIUM,
            'Carbone' => self::RIGGER_MATERIAL_CARBON,
        ];
    }

    public static function getAvailableRiggerPositions(): array
    {
        return [
            'Arrière' => self::RIGGER_POSITION_BACK,
            'Avant' => self::RIGGER_POSITION_FRONT,
        ];
    }

    public static function getAvailableRowerCategories(): array
    {
        return [
            'A' => self::ROWER_CATEGORY_A,
            'B' => self::ROWER_CATEGORY_B,
            'C' => self::ROWER_CATEGORY_C,
        ];
    }

    public static function getAvailableRowingTypes(): array
    {
        return [
            'Les deux' => self::ROWING_TYPE_BOTH,
            'Couple' => self::ROWING_TYPE_SCULL,
            'Pointe' => self::ROWING_TYPE_SWEEP,
        ];
    }

    public static function getAvailableWeightCategories(): array
    {
        return [
            '50-60' => self::WEIGHT_CATEGORY_50,
            '60-70' => self::WEIGHT_CATEGORY_60,
            '70-80' => self::WEIGHT_CATEGORY_70,
            '80-90' => self::WEIGHT_CATEGORY_80,
            '90+' => self::WEIGHT_CATEGORY_90,
        ];
    }
}
