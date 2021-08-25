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

namespace App\Form\Model;

use App\Entity\License;
use App\Entity\MedicalCertificate;
use App\Entity\SeasonCategory;
use App\Entity\User;
use App\Validator as AppAssert;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[AppAssert\UniqueUser]
class RegistrationModel
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    public ?string $phoneNumber = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6, max: 4096)]
    public ?string $plainPassword = null;

    #[Assert\NotBlank]
    public ?string $gender = null;

    #[Assert\NotBlank]
    public ?string $firstName = null;

    #[Assert\NotBlank]
    public ?string $lastName = null;

    #[Assert\NotBlank]
    public ?\DateTime $birthday = null;

    #[Assert\NotBlank]
    public ?string $laneNumber = null;

    #[Assert\NotNull]
    public ?string $laneType = null;

    #[Assert\NotBlank]
    public ?string $laneName = null;

    #[Assert\NotBlank]
    public ?string $postalCode = null;

    #[Assert\NotBlank]
    public ?string $city = null;

    #[Assert\Valid]
    public ?MedicalCertificate $medicalCertificate = null;

    public ?bool $federationEmailAllowed = false;

    public ?bool $clubEmailAllowed = true;

    public ?bool $partnersEmailAllowed = false;

    public function generateUser(SeasonCategory $seasonCategory, UserPasswordHasherInterface $passwordHasher)
    {
        $license = (new License($seasonCategory))
            ->setMedicalCertificate($this->medicalCertificate)
            ->setFederationEmailAllowed($this->federationEmailAllowed)
        ;

        $user = new User();
        $user
            ->setEmail($this->email)
            ->setPhoneNumber($this->phoneNumber)
            ->setPassword($passwordHasher->hashPassword($user, $this->plainPassword))
            ->setGender($this->gender)
            ->setFirstName($this->firstName)
            ->setLastName($this->lastName)
            ->setBirthday($this->birthday)
            ->setLaneNumber($this->laneNumber)
            ->setLaneType($this->laneType)
            ->setLaneName($this->laneName)
            ->setPostalCode($this->postalCode)
            ->setCity($this->city)
            ->addLicense($license)
            ->setClubEmailAllowed($this->clubEmailAllowed)
            ->setPartnersEmailAllowed($this->partnersEmailAllowed)
        ;

        return $user;
    }

    #[Assert\Callback]
    public function validatePhoneNumber(ExecutionContextInterface $context)
    {
        if (null === $this->birthday) {
            return;
        }

        $age = $this->birthday->diff(new \DateTime())->y;
        if ($age < 18 && empty($this->phoneNumber)) {
            $context->buildViolation('Le membre est mineur, merci de renseigner un numéro de téléphone.')
                ->atPath('phoneNumber')
                ->addViolation()
            ;
        }
    }
}
