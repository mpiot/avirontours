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

use App\Enum\MedicalCertificateLevel;
use App\Enum\MedicalCertificateType;
use App\Repository\MedicalCertificateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MedicalCertificateRepository::class)]
class MedicalCertificate
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotBlank(groups: ['Default', 'registration'])]
    #[ORM\Column(enumType: MedicalCertificateType::class)]
    private ?MedicalCertificateType $type = MedicalCertificateType::Certificate;

    #[Assert\NotBlank(groups: ['Default', 'registration'])]
    #[ORM\Column(enumType: MedicalCertificateLevel::class)]
    private ?MedicalCertificateLevel $level = MedicalCertificateLevel::Competition;

    #[Assert\NotBlank(groups: ['Default', 'registration'])]
    #[Assert\GreaterThan(value: '-1 year', message: 'Le certificat médical doit avoir moins d\'un an.', groups: ['Default', 'registration'])]
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[Assert\DisableAutoMapping]
    #[ORM\OneToOne(cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\JoinColumn(nullable: false)]
    private ?UploadedFile $uploadedFile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?MedicalCertificateType
    {
        return $this->type;
    }

    public function setType(?MedicalCertificateType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getLevel(): ?MedicalCertificateLevel
    {
        return $this->level;
    }

    public function setLevel(?MedicalCertificateLevel $level): self
    {
        $this->level = $level;

        return $this;
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

    public function setUploadedFile(UploadedFile $uploadedFile): static
    {
        $this->uploadedFile = $uploadedFile;

        return $this;
    }

    public function getUploadedFile(): ?UploadedFile
    {
        return $this->uploadedFile;
    }
}
