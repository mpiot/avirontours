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
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MemberRepository")
 */
class Member
{
    const NUM_ITEMS = 20;

    const ROWER_CATEGORY_A = 1;
    const ROWER_CATEGORY_B = 2;
    const ROWER_CATEGORY_C = 3;

    const LICENSE_TYPE_ANNUAL = 'A';
    const LICENSE_TYPE_INDOOR = 'I';

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
    private $civility;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $licenseNumber;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $licenseEndAt;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $licenseType;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank()
     */
    private $rowerCategory;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    private $email;

    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank()
     */
    private $birthday;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $legalRepresentative;

    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank()
     */
    private $subscriptionDate;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Address", cascade={"persist", "remove"})
     * @Assert\NotNull()
     * @Assert\Valid()
     */
    private $address;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\LogbookEntry", mappedBy="crewMembers")
     */
    private $logbookEntries;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\User", inversedBy="member")
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\MedicalCertificate", mappedBy="member", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"date": "desc"})
     * @Assert\Valid()
     */
    private $medicalCertificates;

    public function __construct()
    {
        $this->licenseEndAt = new \DateTime('last day of october next year');
        $this->licenseType = self::LICENSE_TYPE_ANNUAL;
        $this->rowerCategory = self::ROWER_CATEGORY_C;
        $this->logbookEntries = new ArrayCollection();
        $this->subscriptionDate = new \DateTimeImmutable();
        $this->medicalCertificates = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getFullName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCivility(): ?string
    {
        return $this->civility;
    }

    public function setCivility(string $civility): self
    {
        $this->civility = $civility;

        return $this;
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

    public function getLicenseEndAt(): ?\DateTimeInterface
    {
        return $this->licenseEndAt;
    }

    public function setLicenseEndAt(?\DateTimeInterface $licenseEndAt): self
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

    public function getLicenseType(): ?string
    {
        return $this->licenseType;
    }

    public function setLicenseType(?string $licenseType): self
    {
        $this->licenseType = $licenseType;

        return $this;
    }

    public function getTextLicenseType(): ?string
    {
        if (self::LICENSE_TYPE_INDOOR === $this->licenseType) {
            return 'Indoor';
        }

        return 'Annuelle';
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

    public function getTextRowerCategory(): string
    {
        switch ($this->getRowerCategory()) {
            case self::ROWER_CATEGORY_A:
                return 'A';
                break;

            case self::ROWER_CATEGORY_B:
                return 'B';
                break;
        }

        return 'C';
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getAge(): int
    {
        $birthday = $this->birthday;

        if (null === $birthday) {
            return 0;
        }

        $interval = $birthday->diff(new \DateTime());

        return $interval->y;
    }

    public function getLegalRepresentative(): ?string
    {
        return $this->legalRepresentative;
    }

    public function setLegalRepresentative(?string $legalRepresentative): self
    {
        $this->legalRepresentative = $legalRepresentative;

        return $this;
    }

    public function getSubscriptionDate(): ?\DateTimeInterface
    {
        return $this->subscriptionDate;
    }

    public function setSubscriptionDate(?\DateTimeInterface $subscriptionDate): self
    {
        $this->subscriptionDate = $subscriptionDate;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): self
    {
        $this->address = $address;

        return $this;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @Assert\Callback()
     */
    public function validateLegalRepresentative(ExecutionContextInterface $context, $payload)
    {
        if ($this->getAge() < 18) {
            $context->buildViolation('Le membre est mineur, merci de renseigner un représentant légal.')
                ->atPath('legalRepresentative')
                ->addViolation();
        }
    }

    /**
     * @return Collection|MedicalCertificate[]
     */
    public function getMedicalCertificates(): Collection
    {
        return $this->medicalCertificates;
    }

    public function addMedicalCertificate(MedicalCertificate $medicalCertificate): self
    {
        if (!$this->medicalCertificates->contains($medicalCertificate)) {
            $this->medicalCertificates[] = $medicalCertificate;
            $medicalCertificate->setMember($this);
        }

        return $this;
    }

    public function removeMedicalCertificate(MedicalCertificate $medicalCertificate): self
    {
        if ($this->medicalCertificates->contains($medicalCertificate)) {
            $this->medicalCertificates->removeElement($medicalCertificate);
            // set the owning side to null (unless already changed)
            if ($medicalCertificate->getMember() === $this) {
                $medicalCertificate->setMember(null);
            }
        }

        return $this;
    }
}
