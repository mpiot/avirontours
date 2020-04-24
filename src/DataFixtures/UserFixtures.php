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

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getUserData() as [$email, $roles, $gender, $firstName, $lastName, $licenseType, $rowerCategory, $birthday, $licenseEndAt]) {
            $user = new User();
            $user
                ->setEmail($email)
                ->setRoles($roles)
                ->setPassword($this->passwordEncoder->encodePassword($user, 'engage'))
                ->setGender($gender)
                ->setFirstName($firstName)
                ->setLastName($lastName)
                ->setLicenseType($licenseType)
                ->setRowerCategory($rowerCategory)
                ->setBirthday($birthday)
                ->setLicenseEndAt($licenseEndAt)
            ;
            $manager->persist($user);
            $this->addReference($user->getUsername(), $user);
        }

        $manager->flush();
    }

    private function getUserData(): array
    {
        return [
            ['super-administrator@avirontours.fr', ['ROLE_SUPER_ADMIN'], 'm', 'Super Admin', 'User', User::LICENSE_TYPE_ANNUAL, User::ROWER_CATEGORY_C, new \DateTime(), new \DateTime('+1 year')],
            ['administrator@avirontours.fr', ['ROLE_SUPER_ADMIN'], 'm', 'Admin', 'User', User::LICENSE_TYPE_ANNUAL, User::ROWER_CATEGORY_C, new \DateTime(), new \DateTime('+1 year')],
            ['annual-a@avirontours.fr', ['ROLE_USER'], 'm', 'A', 'User', User::LICENSE_TYPE_ANNUAL, User::ROWER_CATEGORY_A, new \DateTime(), new \DateTime('+1 year')],
            ['annual-b@avirontours.fr', ['ROLE_USER'], 'm', 'B', 'User', User::LICENSE_TYPE_ANNUAL, User::ROWER_CATEGORY_B, new \DateTime(), new \DateTime('+1 year')],
            ['annual-c@avirontours.fr', ['ROLE_USER'], 'm', 'C', 'User', User::LICENSE_TYPE_ANNUAL, User::ROWER_CATEGORY_C, new \DateTime(), new \DateTime('+1 year')],
            ['indoor@avirontours.fr', ['ROLE_USER'], 'm', 'Indoor', 'User', User::LICENSE_TYPE_INDOOR, User::ROWER_CATEGORY_C, new \DateTime(), new \DateTime('+1 year')],
            ['outdated@avirontours.fr', ['ROLE_USER'], 'm', 'Outdated', 'User', User::LICENSE_TYPE_ANNUAL, User::ROWER_CATEGORY_A, new \DateTime(), new \DateTime('-1 day')],
        ];
    }
}
