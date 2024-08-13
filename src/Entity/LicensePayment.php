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

use App\Enum\PaymentMethod;
use App\Repository\LicensePaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LicensePaymentRepository::class)]
class LicensePayment
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?License $license = null;

    #[Assert\NotNull(groups: ['validate_payment'])]
    #[ORM\Column(length: 255)]
    private ?PaymentMethod $method = null;

    #[Assert\NotBlank(groups: ['validate_payment'])]
    #[Assert\Expression(
        expression: 'null === value or 0 !== value',
        message: 'Cette valeur doit être différente de 0.',
        groups: ['validate_payment']
    )]
    #[ORM\Column]
    private ?int $amount = null;

    #[Assert\When(
        expression: 'true === this.getMethod()?.hasCheckNumber()',
        constraints: [
            new Assert\NotBlank(groups: ['validate_payment']),
        ],
        groups: ['validate_payment']
    )]
    #[ORM\Column(nullable: true)]
    private ?string $checkNumber = null;

    #[Assert\When(
        expression: 'true === this.getMethod()?.hasCheckDate()',
        constraints: [
            new Assert\NotBlank(groups: ['validate_payment']),
        ],
        groups: ['validate_payment']
    )]
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $checkDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLicense(): ?License
    {
        return $this->license;
    }

    public function setLicense(?License $license): static
    {
        $this->license = $license;

        return $this;
    }

    public function getMethod(): ?PaymentMethod
    {
        return $this->method;
    }

    public function setMethod(?PaymentMethod $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(?int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCheckNumber(): ?string
    {
        return $this->checkNumber;
    }

    public function setCheckNumber(?string $checkNumber): static
    {
        $this->checkNumber = $checkNumber;

        return $this;
    }

    public function getCheckDate(): ?\DateTimeImmutable
    {
        return $this->checkDate;
    }

    public function setCheckDate(?\DateTimeImmutable $checkDate): static
    {
        $this->checkDate = $checkDate;

        return $this;
    }
}
