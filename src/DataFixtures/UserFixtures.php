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

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getUserData() as [$email, $roles, $gender, $firstName, $lastName, $birthday]) {
            $user = new User();
            $user
                ->setEmail($email)
                ->setRoles($roles)
                ->setPassword($this->passwordHasher->hashPassword($user, 'engage'))
                ->setGender($gender)
                ->setFirstName($firstName)
                ->setLastName($lastName)
                ->setBirthday($birthday)
            ;
            $manager->persist($user);
            $this->addReference($user->getUsername(), $user);
        }

        $manager->flush();
    }

    private function getUserData(): array
    {
        return [
            ['super-administrator@avirontours.fr', ['ROLE_SUPER_ADMIN'], 'm', 'Super Admin', 'User', new \DateTime(), new \DateTime('+1 year')],
            ['administrator@avirontours.fr', ['ROLE_ADMIN'], 'm', 'Admin', 'User', new \DateTime(), new \DateTime('+1 year')],
            ['annual-a@avirontours.fr', ['ROLE_USER'], 'm', 'On Water', 'User', new \DateTime(), new \DateTime('+1 year')],
            ['indoor@avirontours.fr', ['ROLE_USER'], 'm', 'Indoor', 'User', new \DateTime(), new \DateTime('+1 year')],
            ['outdated@avirontours.fr', ['ROLE_USER'], 'm', 'Outdated', 'User', new \DateTime(), new \DateTime('-1 day')],
        ];
    }
}
