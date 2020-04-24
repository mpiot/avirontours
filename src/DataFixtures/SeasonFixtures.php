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

use App\Entity\Season;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class SeasonFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getSeasonData() as [$name, $licenseEndAt]) {
            $season = new Season();
            $season
                ->setName($name)
                ->setLicenseEndAt($licenseEndAt)
            ;
            $manager->persist($season);
            $this->addReference($name, $season);
        }

        $manager->flush();
    }

    private function getSeasonData(): array
    {
        return [
            [2019, new \DateTime('2020-10-15')],
            [2020, new \DateTime('2021-10-15')],
        ];
    }
}
