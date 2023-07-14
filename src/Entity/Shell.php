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

use App\Repository\ShellRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ShellRepository::class)]
class Shell
{
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

    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/1|2|4|8/', message: 'Le nombre de rameur doit être: 1, 2, 4 ou 8.')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $numberRowers = null;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $coxed = false;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $rowingType = self::ROWING_TYPE_BOTH;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $yolette = false;

    #[Assert\DisableAutoMapping]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $abbreviation = null;

    #[ORM\OneToMany(mappedBy: 'shell', targetEntity: 'App\Entity\LogbookEntry')]
    private Collection $logbookEntries;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $productionYear = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $weightCategory = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $newPrice = null;

    #[Assert\NotBlank]
    #[Assert\GreaterThanOrEqual(value: 0)]
    #[ORM\Column(type: Types::FLOAT)]
    private ?float $mileage = 0.0;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $riggerMaterial = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $riggerPosition = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $usageFrequency = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $personalBoat = false;

    #[ORM\OneToMany(mappedBy: 'shell', targetEntity: 'App\Entity\ShellDamage', cascade: ['remove'])]
    private Collection $shellDamages;

    public function __construct()
    {
        $this->logbookEntries = new ArrayCollection();
        $this->shellDamages = new ArrayCollection();
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
        return array_flip(self::getAvailableRowingTypes())[$this->rowingType];
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
        $crewSize = $this->getNumberRowers() ?? 0;

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

    public function getWeightCategory(): ?int
    {
        return $this->weightCategory;
    }

    public function getTextWeightCategory($withUnit = true): ?string
    {
        $text = array_flip(self::getAvailableWeightCategories())[$this->weightCategory];

        if (true === $withUnit) {
            $text .= ' Kg';
        }

        return $text;
    }

    public function setWeightCategory(?int $weightCategory): self
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
        return array_flip(self::getAvailableRiggerMaterials())[$this->riggerMaterial];
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
        return array_flip(self::getAvailableRiggerPositions())[$this->riggerPosition];
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
