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

use App\Entity\Shell;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ShellFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getShellData() as [$name, $nbRowers, $coxed, $rowingType]) {
            $shell = new Shell();
            $shell
                ->setName($name)
                ->setNumberRowers($nbRowers)
                ->setCoxed($coxed)
                ->setRowingType($rowingType)
            ;
            $manager->persist($shell);
            $this->addReference($name, $shell);
        }

        $manager->flush();
    }

    private function getShellData(): array
    {
        return [
            ['Double', 2, false, Shell::ROWING_TYPE_BOTH],
            ['Four', 4, false, Shell::ROWING_TYPE_BOTH],
            ['Eight', 8, false, Shell::ROWING_TYPE_BOTH],
            ['Single highly damaged', 1, false, Shell::ROWING_TYPE_SCULL],
            ['Single medium damaged', 1, false, Shell::ROWING_TYPE_SCULL],
        ];
    }
}
