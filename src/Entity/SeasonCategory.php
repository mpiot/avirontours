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
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SeasonCategoryRepository")
 */
class SeasonCategory
{
    const LICENSE_TYPE_ANNUAL = 'A';
    const LICENSE_TYPE_UNIVERSITY = 'U';
    const LICENSE_TYPE_INDOOR = 'I';
    const LICENSE_TYPE_DISCOVERY_7D = 'D_7D';
    const LICENSE_TYPE_DISCOVERY_30D = 'D_30D';
    const LICENSE_TYPE_DISCOVERY_90D = 'D_90D';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Season", inversedBy="seasonCategories")
     * @ORM\JoinColumn(nullable=false)
     */
    private $season;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @ORM\Column(type="float")
     * @Assert\NotBlank()
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=5)
     * @Assert\NotBlank()
     */
    private $licenseType;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\License", mappedBy="seasonCategory")
     */
    private $licenses;

    /**
     * @Gedmo\Slug(handlers={
     *      @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\RelativeSlugHandler", options={
     *          @Gedmo\SlugHandlerOption(name="relationField", value="season"),
     *          @Gedmo\SlugHandlerOption(name="relationSlugField", value="name"),
     *          @Gedmo\SlugHandlerOption(name="separator", value="-"),
     *          @Gedmo\SlugHandlerOption(name="urilize", value=true)
     *      })
     * }, fields={"name"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

    /**
     * @ORM\Column(type="boolean")
     */
    private $displayed;

    public function __construct()
    {
        $this->licenses = new ArrayCollection();
        $this->displayed = true;
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

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getLicenseType(): ?string
    {
        return $this->licenseType;
    }

    public function setLicenseType(string $licenseType): self
    {
        $this->licenseType = $licenseType;

        return $this;
    }

    public function getTextLicenseType(): ?string
    {
        return array_flip(self::getAvailableLicenseTypes())[$this->licenseType];
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

    public static function getAvailableLicenseTypes(): array
    {
        return [
            'Licence Annuelle' => self::LICENSE_TYPE_ANNUAL,
            'Licence Universitaire' => self::LICENSE_TYPE_UNIVERSITY,
            'Licence Indoor' => self::LICENSE_TYPE_INDOOR,
            'Licence Découverte - 7 jours' => self::LICENSE_TYPE_DISCOVERY_7D,
            'Licence Découverte - 30 jours' => self::LICENSE_TYPE_DISCOVERY_30D,
            'Licence Découverte - 90 jours' => self::LICENSE_TYPE_DISCOVERY_90D,
        ];
    }
}
