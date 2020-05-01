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

use App\Entity\License;
use App\Entity\MedicalCertificate;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LicenseFixturesFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getSeasonData() as [$seasonCategory, $user, $certificateLevel]) {
            $license = new License($seasonCategory, $user);
            $license
                ->setMedicalCertificate(
                    (new MedicalCertificate())
                        ->setType(MedicalCertificate::TYPE_CERTIFICATE)
                        ->setLevel($certificateLevel)
                        ->setDate(new \DateTime('-3 months'))
                )
            ;
            $manager->persist($license);
            $this->addReference($seasonCategory->getName().'-'.$user->getFullName(), $license);
        }

        $manager->flush();
    }

    private function getSeasonData(): array
    {
        $seasonCategory = $this->getReference('2019')->getSeasonCategories()->first();

        return [
            [$seasonCategory, $this->getReference('a.user'), MedicalCertificate::LEVEL_PRACTICE],
            [$seasonCategory, $this->getReference('b.user'), MedicalCertificate::LEVEL_COMPETITION],
            [$seasonCategory, $this->getReference('c.user'), MedicalCertificate::LEVEL_COMPETITION],
            [$seasonCategory, $this->getReference('indoor.user'), MedicalCertificate::LEVEL_PRACTICE],
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
