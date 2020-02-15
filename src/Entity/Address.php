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
 * @ORM\Entity(repositoryClass="App\Repository\AddressRepository")
 */
class Address
{
    const USABLE_NO = 0;
    const USABLE_INTERNAL = 1;
    const USABLE_YES = 2;

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
    private $number;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $laneType;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $laneName;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank()
     */
    private $postalCode;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $phoneNumber;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $usable;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getLaneType(): ?string
    {
        return $this->laneType;
    }

    public function setLaneType(string $laneType): self
    {
        $this->laneType = $laneType;

        return $this;
    }

    public function getLaneName(): ?string
    {
        return $this->laneName;
    }

    public function setLaneName(string $laneName): self
    {
        $this->laneName = $laneName;

        return $this;
    }

    public function getPostalCode(): ?int
    {
        return $this->postalCode;
    }

    public function setPostalCode(int $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getUsable(): ?string
    {
        return $this->usable;
    }

    public function setUsable(string $usable): self
    {
        $this->usable = $usable;

        return $this;
    }

    public function getTextUsable(): string
    {
        switch ($this->getUsable()) {
            case self::USABLE_INTERNAL:
                return 'Interne';
                break;

            case self::USABLE_YES:
                return 'Oui';
                break;
        }

        return 'Non';
    }

    public function getFormattedAddress(): string
    {
        $address = "{$this->getNumber()}, {$this->getLaneType()} {$this->getLaneName()}\n";
        $address .= "{$this->getPostalCode()} {$this->getCity()}";

        return $address;
    }
}
