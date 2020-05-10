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

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LicenseRepository")
 * @UniqueEntity(fields={"seasonCategory", "user"}, message="Déjà inscrit pour cette saison.")
 */
class License
{
    const NUM_ITEMS = 20;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\SeasonCategory", inversedBy="licenses")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull()
     */
    private $seasonCategory;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="licenses")
     * @ORM\JoinColumn(name="app_user", nullable=false)
     */
    private $user;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\MedicalCertificate", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull()
     * @Assert\Valid()
     */
    private $medicalCertificate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $licenseNumber;

    public function __construct(SeasonCategory $seasonCategory = null, User $user = null)
    {
        $this->seasonCategory = $seasonCategory;
        $this->user = $user;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeasonCategory(): ?SeasonCategory
    {
        return $this->seasonCategory;
    }

    public function setSeasonCategory(?SeasonCategory $seasonCategory): self
    {
        $this->seasonCategory = $seasonCategory;

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

    public function getMedicalCertificate(): ?MedicalCertificate
    {
        return $this->medicalCertificate;
    }

    public function setMedicalCertificate(MedicalCertificate $medicalCertificate): self
    {
        $this->medicalCertificate = $medicalCertificate;

        return $this;
    }

    public function getLicenseNumber(): ?string
    {
        return $this->licenseNumber;
    }

    public function setLicenseNumber(string $licenseNumber): self
    {
        $this->licenseNumber = $licenseNumber;

        return $this;
    }
}
