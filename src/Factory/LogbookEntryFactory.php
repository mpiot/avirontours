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

namespace App\Factory;

use App\Entity\LogbookEntry;
use App\Repository\LogbookEntryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @method static             LogbookEntry|Proxy createOne(array $attributes = [])
 * @method static             LogbookEntry[]|Proxy[] createMany(int $number, $attributes = [])
 * @method static             LogbookEntry|Proxy findOrCreate(array $attributes)
 * @method static             LogbookEntry|Proxy random(array $attributes = [])
 * @method static             LogbookEntry|Proxy randomOrCreate(array $attributes = [])
 * @method static             LogbookEntry[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static             LogbookEntry[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static             LogbookEntryRepository|RepositoryProxy repository()
 * @method LogbookEntry|Proxy create($attributes = [])
 */
final class LogbookEntryFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        $shell = ShellFactory::new();
        $finished = self::faker()->boolean();

        return [
            'shell' => $shell,
            'endAt' => $finished ? new \DateTime('+1 hour') : null,
            'coveredDistance' => $finished ? self::faker()->numberBetween(2, 20) : null,
            'shellDamages' => ShellDamageFactory::new()->many(0, 3),
        ];
    }

    public function withActiveCrew(int $number): self
    {
        $licences = LicenseFactory::new()->annualActive()->many($number);
        $crew = [];
        foreach ($licences as $license) {
            $crew[] = $license->getUser();
        }

        return $this->addState([
            'crewMembers' => $crew,
        ]);
    }

    public function withInactiveCrew(int $number): self
    {
        $licences = LicenseFactory::new()->annualInactive()->many($number);
        $crew = [];
        foreach ($licences as $license) {
            $crew[] = $license->getUser();
        }

        return $this->addState([
            'crewMembers' => $crew,
        ]);
    }

    public function finished(): self
    {
        return $this->addState([
            'endAt' => new \DateTime('+1 hour'),
            'coveredDistance' => self::faker()->numberBetween(2, 20),
        ]);
    }

    public function notFinished(): self
    {
        return $this->addState([
            'endAt' => null,
            'coveredDistance' => null,
        ]);
    }

    public function withDamages(): self
    {
        return $this->addState([
            'shellDamages' => ShellDamageFactory::new()->many(1, 3),
        ]);
    }

    public function withoutDamages(): self
    {
        return $this->addState([
            'shellDamages' => new ArrayCollection(),
        ]);
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this
            // ->beforeInstantiate(function(LogbookEntry $logbookEntry) {})
        ;
    }

    protected static function getClass(): string
    {
        return LogbookEntry::class;
    }
}
