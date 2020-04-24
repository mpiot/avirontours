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

namespace App\Form\Model;

use App\Entity\MedicalCertificate;
use App\Entity\Season;
use App\Entity\SeasonUser;
use App\Entity\User;
use App\Validator\UniqueUser;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @UniqueUser()
 */
class RegistrationModel
{
    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    public $email;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(min="6", max="4096")
     */
    public $plainPassword;

    /**
     * @var string
     * @Assert\NotBlank()
     */
    public $gender;

    /**
     * @var string
     * @Assert\NotBlank()
     */
    public $firstName;

    /**
     * @var string
     * @Assert\NotBlank()
     */
    public $lastName;

    /**
     * @var \DateTime
     * @Assert\NotBlank()
     */
    public $birthday;

    /**
     * @var string
     */
    public $legalRepresentative;

    /**
     * @var int
     * @Assert\NotBlank()
     */
    public $laneNumber;

    /**
     * @var string
     * @Assert\NotBlank()
     */
    public $laneType;

    /**
     * @var string
     * @Assert\NotBlank()
     */
    public $laneName;

    /**
     * @var int
     * @Assert\NotBlank()
     */
    public $postalCode;

    /**
     * @var string
     * @Assert\NotBlank()
     */
    public $city;

    /**
     * @var string
     * @Assert\NotBlank()
     */
    public $phoneNumber;

    /**
     * @var MedicalCertificate
     * @Assert\Valid()
     */
    public $medicalCertificate;

    public function generateUser(string $licenseType, Season $season, UserPasswordEncoderInterface $passwordEncoder)
    {
        $licenseType = $licenseType = 'indoor' === $licenseType ? User::LICENSE_TYPE_INDOOR : User::LICENSE_TYPE_ANNUAL;

        $seasonUser = (new SeasonUser())
            ->setMedicalCertificate($this->medicalCertificate)
            ->setSeason($season)
        ;

        $user = new User();
        $user
            ->setEmail($this->email)
            ->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $this->plainPassword
                )
            )
            ->setGender($this->gender)
            ->setFirstName($this->firstName)
            ->setLastName($this->lastName)
            ->setBirthday($this->birthday)
            ->setLegalRepresentative($this->legalRepresentative)
            ->setLaneNumber($this->laneNumber)
            ->setLaneType($this->laneType)
            ->setLaneName($this->laneName)
            ->setPostalCode($this->postalCode)
            ->setCity($this->city)
            ->setPhoneNumber($this->phoneNumber)
            ->setLicenseType($licenseType)
            ->addSeasonUser($seasonUser)
        ;

        return $user;
    }

    /**
     * @Assert\Callback()
     */
    public function validateLegalRepresentative(ExecutionContextInterface $context, $payload)
    {
        $birthday = empty($this->birthday) ? 0 : $this->birthday->diff(new \DateTime())->y;

        if ($birthday < 18 && empty($this->legalRepresentative)) {
            $context->buildViolation('Le membre est mineur, merci de renseigner un représentant légal.')
                ->atPath('legalRepresentative')
                ->addViolation();
        }
    }
}
