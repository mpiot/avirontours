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

use App\Enum\LicenseType;
use App\Repository\SeasonCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Handler\RelativeSlugHandler;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(['season', 'name'])]
#[ORM\Entity(repositoryClass: SeasonCategoryRepository::class)]
class SeasonCategory
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Season', inversedBy: 'seasonCategories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Season $season = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::FLOAT)]
    private ?float $price = null;

    #[Assert\NotBlank]
    #[ORM\Column(length: 5, enumType: LicenseType::class)]
    private ?LicenseType $licenseType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'seasonCategory', targetEntity: 'App\Entity\License')]
    private Collection $licenses;

    #[Gedmo\Slug(fields: ['name'])]
    #[Gedmo\SlugHandler(class: RelativeSlugHandler::class, options: [
        'relationField' => 'season',
        'relationSlugField' => 'name',
        'separator' => '-',
        'urilize' => true,
    ])]
    #[ORM\Column(length: 128, unique: true)]
    private string $slug;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $displayed = true;

    public function __construct()
    {
        $this->licenses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(?Season $season): self
    {
        $this->season = $season;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getLicenseType(): ?LicenseType
    {
        return $this->licenseType;
    }

    public function setLicenseType(?LicenseType $licenseType): self
    {
        $this->licenseType = $licenseType;

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

    /**
     * @return Collection|License[]
     */
    public function getLicenses(): Collection
    {
        return $this->licenses;
    }

    public function addLicense(License $license): self
    {
        if (!$this->licenses->contains($license)) {
            $this->licenses[] = $license;
            $license->setSeasonCategory($this);
        }

        return $this;
    }

    public function removeLicense(License $license): self
    {
        if ($this->licenses->contains($license)) {
            $this->licenses->removeElement($license);
            // set the owning side to null (unless already changed)
            if ($license->getSeasonCategory() === $this) {
                $license->setSeasonCategory(null);
            }
        }

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDisplayed(): ?bool
    {
        return $this->displayed;
    }

    public function setDisplayed(bool $displayed): self
    {
        $this->displayed = $displayed;

        return $this;
    }
}
