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
use App\Entity\SeasonCategory;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class SeasonFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getSeasonData() as [$name, $active, $subscription]) {
            $season = new Season();
            $season
                ->setName($name)
                ->setActive($active)
                ->setSubscriptionEnabled($subscription)
            ;

            foreach ($this->getSeasonCategoryData() as [$categoryName, $price, $licenseType, $description]) {
                $seasonCategory = new SeasonCategory();
                $seasonCategory
                    ->setName($categoryName)
                    ->setPrice($price)
                    ->setLicenseType($licenseType)
                    ->setDescription($description)
                ;
                $season->addSeasonCategory($seasonCategory);
            }

            $manager->persist($season);
            $this->addReference($name, $season);
        }

        $manager->flush();
    }

    private function getSeasonData(): array
    {
        return [
            [2018, false, false],
            [2019, true, false],
            [2020, true, true],
        ];
    }

    private function getSeasonCategoryData(): array
    {
        return [
            ['Jeune', 214.0, User::LICENSE_TYPE_ANNUAL, null],
            ['Etudiant', 256.0, User::LICENSE_TYPE_ANNUAL, null],
            ['Adulte', 320.0, User::LICENSE_TYPE_ANNUAL, null],
            ['Indoor', 130.0, User::LICENSE_TYPE_INDOOR, null],
        ];
    }
}
