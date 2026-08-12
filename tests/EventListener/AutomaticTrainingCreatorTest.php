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

namespace App\Tests\EventListener;

use App\Entity\LogbookEntry;
use App\Enum\SportType;
use App\Factory\LogbookEntryFactory;
use App\Factory\ShellFactory;
use App\Factory\TrainingFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;

class AutomaticTrainingCreatorTest extends AppWebTestCase
{
    public function testInsertingAnAlreadyFinishedEntryCreatesTheTraining(): void
    {
        self::ensureKernelShutdown();
        static::createClient();
        $entityManager = self::getEntityManager();

        $user = UserFactory::new(['automaticTraining' => true])->major()->create();

        $logbookEntry = new LogbookEntry();
        $logbookEntry
            ->setShell(ShellFactory::createOne())
            ->setEndAt(new \DateTime('+1 hour'))
            ->setCoveredDistance(10)
            ->addCrewMember($user)
        ;

        $entityManager->persist($logbookEntry);
        $entityManager->flush();

        TrainingFactory::assert()->count(1);

        $training = TrainingFactory::repository()->last();
        $this->assertSame($logbookEntry->getDate()->format('Y-m-d'), $training->getTrainedAt()->format('Y-m-d'));
        $this->assertSame($logbookEntry->getStartAt()->format('H:i:s'), $training->getTrainedAt()->format('H:i:s'));
        $this->assertSame(SportType::Rowing, $training->getSport());
        $this->assertSame(36000, $training->getDuration());
        $this->assertSame(10000, $training->getDistance());
        $this->assertNull($training->getFeeling());
        $this->assertNull($training->getRatedPerceivedExertion());
    }

    public function testNoTrainingIsCreatedForAMemberWhoOptedOut(): void
    {
        self::ensureKernelShutdown();
        static::createClient();
        $entityManager = self::getEntityManager();

        $user = UserFactory::new(['automaticTraining' => false])->major()->create();

        $logbookEntry = new LogbookEntry();
        $logbookEntry
            ->setShell(ShellFactory::createOne())
            ->setEndAt(new \DateTime('+1 hour'))
            ->setCoveredDistance(10)
            ->addCrewMember($user)
        ;

        $entityManager->persist($logbookEntry);
        $entityManager->flush();

        TrainingFactory::assert()->count(0);
    }

    public function testFinishingAnEntryCreatesTheTrainingExactlyOnce(): void
    {
        self::ensureKernelShutdown();
        static::createClient();
        $entityManager = self::getEntityManager();

        $user = UserFactory::new(['automaticTraining' => true])->major()->create();
        $logbookEntry = LogbookEntryFactory::createOne([
            'shell' => ShellFactory::createOne(),
            'crewMembers' => [$user],
            'endAt' => null,
            'coveredDistance' => null,
        ]);
        $entityManager->flush();

        TrainingFactory::assert()->count(0);

        $logbookEntry->setEndAt(new \DateTime('+1 hour'))->setCoveredDistance(8);
        $entityManager->flush();

        TrainingFactory::assert()->count(1);

        $logbookEntry->setDate(new \DateTime('-1 day'));
        $entityManager->flush();

        TrainingFactory::assert()->count(1);
    }
}
