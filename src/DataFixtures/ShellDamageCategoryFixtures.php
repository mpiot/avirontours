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

use App\Entity\ShellDamageCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ShellDamageCategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getShellDamageCategoryData() as [$name, $priority]) {
            $shellDamageCategory = new ShellDamageCategory();
            $shellDamageCategory
                ->setName($name)
                ->setPriority($priority)
            ;
            $manager->persist($shellDamageCategory);
            $this->addReference($name, $shellDamageCategory);
        }

        $manager->flush();
    }

    private function getShellDamageCategoryData(): array
    {
        return [
            ['ShellDamage Category High', ShellDamageCategory::PRIORITY_HIGH],
            ['ShellDamage Category Medium', ShellDamageCategory::PRIORITY_MEDIUM],
            ['ShellDamage Category To Delete', ShellDamageCategory::PRIORITY_MEDIUM],
        ];
    }
}
