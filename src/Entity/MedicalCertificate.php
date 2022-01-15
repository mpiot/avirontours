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

use App\Repository\MedicalCertificateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: MedicalCertificateRepository::class)]
class MedicalCertificate
{
    public const TYPE_CERTIFICATE = 'certificate';
    public const TYPE_ATTESTATION = 'attestation';
    public const LEVEL_PRACTICE = 'practice';
    public const LEVEL_COMPETITION = 'competition';
    public const LEVEL_UPGRADE = 'upgrade';

    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $type = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $level = null;

    #[Assert\NotBlank]
    #[Assert\GreaterThan(value: '-1 year', message: 'Le certificat médical doit avoir moins d\'un an.')]
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     */
    #[Assert\NotNull(groups: ['new'])]
    #[Assert\File(
        maxSize: '3M',
        mimeTypes: ['application/pdf', 'application/x-pdf', 'image/*'],
        mimeTypesMessage: 'Le fichier doit être au format PDF ou bien une image.'
    )]
    #[Vich\UploadableField(mapping: 'medical_certificate', fileNameProperty: 'fileName', size: 'fileSize', mimeType: 'fileMimeType')]
    private ?File $file = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $fileName = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $fileSize = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $fileMimeType = null;

    /**
     * @var \DateTimeInterface|null
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $updatedAt = null;

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

    public function getTextType(): ?string
    {
        return array_flip(self::getAvailableTypes())[$this->type];
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function getTextLevel(): ?string
    {
        return array_flip(self::getAvailableLevels())[$this->level];
    }

    public function setLevel(?string $level): self
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

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $file
     */
    public function setFile(?File $file = null): self
    {
        $this->file = $file;

        if (null !== $file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime();
        }

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFileName(?string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileSize(?int $fileSize): self
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileMimeType(?string $fileMimeType): self
    {
        $this->fileMimeType = $fileMimeType;

        return $this;
    }

    public function getFileMimeType(): ?string
    {
        return $this->fileMimeType;
    }

    public static function getAvailableLevels(): array
    {
        return [
            'Pratique' => self::LEVEL_PRACTICE,
            'Compétition' => self::LEVEL_COMPETITION,
            'Surclassement' => self::LEVEL_UPGRADE,
        ];
    }

    public static function getAvailableTypes(): array
    {
        return [
            'Attestation' => self::TYPE_ATTESTATION,
            'Certificat' => self::TYPE_CERTIFICATE,
        ];
    }
}
