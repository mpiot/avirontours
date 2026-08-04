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

use App\Factory\LogbookEntryFactory;
use App\Factory\ShellFactory;
use App\Tests\AppWebTestCase;

class ShellMileageUpdaterTest extends AppWebTestCase
{
    public function testMileageFollowsTheEntryWhenTheShellChanges(): void
    {
        self::ensureKernelShutdown();
        static::createClient();
        $entityManager = self::getEntityManager();

        $shellA = ShellFactory::createOne(['mileage' => 0]);
        $shellB = ShellFactory::createOne(['mileage' => 0]);

        $entry = LogbookEntryFactory::createOne(['shell' => $shellA, 'coveredDistance' => 10, 'endAt' => new \DateTime()]);
        $entityManager->flush();

        $this->assertSame(10.0, $shellA->getMileage());
        $this->assertSame(0.0, $shellB->getMileage());

        $entry->setShell($shellB);
        $entityManager->flush();

        $this->assertSame(0.0, $shellA->getMileage());
        $this->assertSame(10.0, $shellB->getMileage());
    }

    public function testAnUnrelatedEntryInTheSameFlushDoesNotReapplyAnotherEntrysMileage(): void
    {
        self::ensureKernelShutdown();
        static::createClient();
        $entityManager = self::getEntityManager();

        $shellA = ShellFactory::createOne(['mileage' => 0]);
        $shellB = ShellFactory::createOne(['mileage' => 0]);

        $movedEntry = LogbookEntryFactory::createOne(['shell' => $shellA, 'coveredDistance' => 10, 'endAt' => new \DateTime()]);
        $untouchedEntry = LogbookEntryFactory::createOne(['shell' => $shellA, 'coveredDistance' => 7, 'endAt' => new \DateTime()]);
        $entityManager->flush();

        $this->assertSame(17.0, $shellA->getMileage());
        $this->assertSame(0.0, $shellB->getMileage());

        $movedEntry->setShell($shellB);
        $untouchedEntry->setDate(new \DateTime('-1 day'));
        $entityManager->flush();

        $this->assertSame(7.0, $shellA->getMileage());
        $this->assertSame(10.0, $shellB->getMileage());
    }

    public function testMileageIsGivenBackWhenTheEntryIsDeleted(): void
    {
        self::ensureKernelShutdown();
        static::createClient();
        $entityManager = self::getEntityManager();

        $shell = ShellFactory::createOne(['mileage' => 0]);
        $entry = LogbookEntryFactory::createOne(['shell' => $shell, 'coveredDistance' => 10, 'endAt' => new \DateTime()]);
        $entityManager->flush();

        $this->assertSame(10.0, $shell->getMileage());

        $entityManager->remove($entry);
        $entityManager->flush();

        $this->assertSame(0.0, $shell->getMileage());
    }
}
