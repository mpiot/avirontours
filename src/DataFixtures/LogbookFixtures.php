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

use App\Entity\LogbookEntry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LogbookFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getShellData() as [$shell, $crewMembers, $endAt, $coveredDistance]) {
            $logbookEntry = new LogbookEntry();
            $logbookEntry
                ->setShell($shell)
                ->setEndAt($endAt)
                ->setCoveredDistance($coveredDistance)
            ;

            foreach ($crewMembers as $crewMember) {
                $logbookEntry->addCrewMember($crewMember);
            }

            $manager->persist($logbookEntry);
            $this->addReference('logbook_entry_'.$shell->getName(), $logbookEntry);
        }

        $manager->flush();
    }

    private function getShellData(): array
    {
        return [
            [$this->getReference('Double Cat B'), [$this->getReference('a.user'), $this->getReference('b.user')], new \DateTime('+1 hour'), 10],
            [$this->getReference('Single medium damaged'), [$this->getReference('admin.user')], null, null],
        ];
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
            ShellFixtures::class,
        ];
    }
}
