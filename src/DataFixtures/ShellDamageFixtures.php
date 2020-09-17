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

use App\Entity\ShellDamage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ShellDamageFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getShellDamageData() as [$category, $shell, $description]) {
            $shellDamage = new ShellDamage();
            $shellDamage
                ->setCategory($category)
                ->setShell($shell)
                ->setDescription($description)
            ;
            $manager->persist($shellDamage);
            $this->addReference($category->getName().'-'.$shell->getName(), $shellDamage);
        }

        $manager->flush();
    }

    private function getShellDamageData(): array
    {
        return [
            [$this->getReference('ShellDamage Category High'), $this->getReference('Single highly damaged'), 'A description...'],
            [$this->getReference('ShellDamage Category Medium'), $this->getReference('Single medium damaged'), 'A description...'],
        ];
    }

    public function getDependencies()
    {
        return [
            ShellDamageCategoryFixtures::class,
            ShellFixtures::class,
        ];
    }
}
