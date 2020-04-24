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

use App\Entity\MedicalCertificate;
use App\Entity\SeasonUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class SeasonUserFixturesFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getSeasonData() as [$season, $user, $certificateLevel]) {
            $seasonUser = new SeasonUser();
            $seasonUser
                ->setSeason($season)
                ->setUser($user)
                ->setMedicalCertificate(
                    (new MedicalCertificate())
                        ->setType(MedicalCertificate::TYPE_CERTIFICATE)
                        ->setLevel($certificateLevel)
                        ->setDate(new \DateTime('-3 months'))
                )
            ;
            $manager->persist($seasonUser);
            $this->addReference($season->getName().'-'.$user->getFullName(), $seasonUser);
        }

        $manager->flush();
    }

    private function getSeasonData(): array
    {
        return [
            [$this->getReference('2019'), $this->getReference('a.user'), MedicalCertificate::LEVEL_PRACTICE],
            [$this->getReference('2019'), $this->getReference('b.user'), MedicalCertificate::LEVEL_COMPETITION],
            [$this->getReference('2019'), $this->getReference('c.user'), MedicalCertificate::LEVEL_COMPETITION],
            [$this->getReference('2019'), $this->getReference('indoor.user'), MedicalCertificate::LEVEL_PRACTICE],
        ];
    }

    public function getDependencies()
    {
        return [
            SeasonFixtures::class,
            UserFixtures::class,
        ];
    }
}
