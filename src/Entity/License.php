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

use App\Entity\Traits\TimestampableEntity;
use App\Repository\LicenseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['seasonCategory', 'user'], message: 'Déjà inscrit pour cette saison.')]
#[ORM\Entity(repositoryClass: LicenseRepository::class)]
class License
{
    use BlameableEntity;
    use TimestampableEntity;

    public const NUM_ITEMS = 20;

    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $uuid;

    #[Assert\NotNull]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\SeasonCategory', inversedBy: 'licenses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SeasonCategory $seasonCategory;

    #[Assert\NotNull]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', inversedBy: 'licenses')]
    #[ORM\JoinColumn(name: 'app_user', nullable: false)]
    private ?User $user;

    #[ORM\Column(type: Types::JSON)]
    private array $marking = [];

    #[ORM\Column(type: Types::JSON)]
    private array $transitionContexts = [];

    #[Assert\NotNull(groups: ['Default', 'registration'])]
    #[Assert\Valid(groups: ['Default', 'registration'])]
    #[ORM\OneToOne(targetEntity: 'App\Entity\MedicalCertificate', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?MedicalCertificate $medicalCertificate = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $optionalInsurance = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $federationEmailAllowed = false;

    #[Assert\Positive]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $logbookEntryLimit = null;

    #[Assert\Count(min: 1, groups: ['validate_payment'])]
    #[Assert\Valid(groups: ['validate_payment'])]
    #[ORM\OneToMany(mappedBy: 'license', targetEntity: LicensePayment::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $payments;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $payedAt = null;

    public function __construct(SeasonCategory $seasonCategory = null)
    {
        $this->uuid = Uuid::v4();
        $this->seasonCategory = $seasonCategory;
        $this->payments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
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

    public function getMarking()
    {
        return $this->marking;
    }

    public function isValid(): bool
    {
        return null !== $this->marking && (
            (\array_key_exists('validated', $this->marking) && 1 === $this->marking['validated'])
            || (
                (\array_key_exists('medical_certificate_validated', $this->marking) && 1 === $this->marking['medical_certificate_validated'])
                && (\array_key_exists('payment_validated', $this->marking) && 1 === $this->marking['payment_validated'])
            )
        );
    }

    public function setMarking($marking, $context = []): void
    {
        $this->marking = $marking;
        $this->transitionContexts[] = [
            'new_marking' => $marking,
            'context' => $context,
            'time' => (new \DateTime())->format('c'),
        ];
    }

    public function getTransitionContexts(): array
    {
        return $this->transitionContexts;
    }

    public function setTransitionContexts($transitionContexts): void
    {
        $this->transitionContexts = $transitionContexts;
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

    public function getOptionalInsurance(): bool
    {
        return $this->optionalInsurance;
    }

    public function setOptionalInsurance(bool $optionalInsurance): self
    {
        $this->optionalInsurance = $optionalInsurance;

        return $this;
    }

    public function getFederationEmailAllowed(): bool
    {
        return $this->federationEmailAllowed;
    }

    public function setFederationEmailAllowed(bool $federationEmailAllowed): self
    {
        $this->federationEmailAllowed = $federationEmailAllowed;

        return $this;
    }

    public function getLogbookEntryLimit(): ?int
    {
        return $this->logbookEntryLimit;
    }

    public function setLogbookEntryLimit(?int $logbookEntryLimit): self
    {
        $this->logbookEntryLimit = $logbookEntryLimit;

        return $this;
    }

    /**
     * @return Collection<int, LicensePayment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(LicensePayment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setLicense($this);
        }

        return $this;
    }

    public function removePayment(LicensePayment $payment): static
    {
        // set the owning side to null (unless already changed)
        if ($this->payments->removeElement($payment) && $payment->getLicense() === $this) {
            $payment->setLicense(null);
        }

        return $this;
    }

    public function getPaymentsAmount(): int
    {
        return $this->payments->reduce(fn (int $carrier, LicensePayment $payment) => $carrier + $payment->getAmount(), 0);
    }

    public function getPayedAt(): ?\DateTimeImmutable
    {
        return $this->payedAt;
    }

    public function setPayedAt(?\DateTimeImmutable $payedAt): static
    {
        $this->payedAt = $payedAt;

        return $this;
    }
}
