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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MedicalCertificateRepository")
 */
class MedicalCertificate
{
    const TYPE_CERTIFICATE = 'certificate';
    const TYPE_ATTESTATION = 'attestation';

    const LEVEL_PRACTICE = 'practice';
    const LEVEL_COMPETITION = 'competition';
    const LEVEL_UPGRADE = 'upgrade';

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
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $level;

    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank()
     * @Assert\GreaterThan("-1 year", message="Le certificat médical doit avoir moins d'un an.")
     */
    private $date;

    public function __construct()
    {
        $this->type = self::TYPE_CERTIFICATE;
        $this->level = self::LEVEL_COMPETITION;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTextType(): string
    {
        if (self::TYPE_ATTESTATION === $this->type) {
            return 'Attestation';
        }

        return 'Certificat';
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(?string $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getTextLevel(): string
    {
        if (self::LEVEL_PRACTICE === $this->level) {
            return 'Attestation';
        }

        if (self::LEVEL_UPGRADE === $this->level) {
            return 'Surclassement';
        }

        return 'Pratique';
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(?\DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }
}
