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

use App\Entity\LogBookEntry;
use App\Repository\LogBookEntryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentProxyObjectFactory<LogBookEntry>
 *
 * @method        LogBookEntry|\Zenstruck\Foundry\Persistence\Proxy create(array|callable $attributes = [])
 * @method static LogBookEntry|Proxy                                createOne(array $attributes = [])
 * @method static LogBookEntry|Proxy                                find(object|array|mixed $criteria)
 * @method static LogBookEntry|Proxy                                findOrCreate(array $attributes)
 * @method static LogBookEntry|Proxy                                first(string $sortedField = 'id')
 * @method static LogBookEntry|Proxy                                last(string $sortedField = 'id')
 * @method static LogBookEntry|Proxy                                random(array $attributes = [])
 * @method static LogBookEntry|Proxy                                randomOrCreate(array $attributes = [])
 * @method static LogBookEntryRepository|ProxyRepositoryDecorator   repository()
 * @method static LogBookEntry[]|Proxy[]                            all()
 * @method static LogBookEntry[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static LogBookEntry[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static LogBookEntry[]|Proxy[]                            findBy(array $attributes)
 * @method static LogBookEntry[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static LogBookEntry[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        LogBookEntry&Proxy<LogBookEntry> create(array|callable $attributes = [])
 * @phpstan-method static LogBookEntry&Proxy<LogBookEntry> createOne(array $attributes = [])
 * @phpstan-method static LogBookEntry&Proxy<LogBookEntry> find(object|array|mixed $criteria)
 * @phpstan-method static LogBookEntry&Proxy<LogBookEntry> findOrCreate(array $attributes)
 * @phpstan-method static LogBookEntry&Proxy<LogBookEntry> first(string $sortedField = 'id')
 * @phpstan-method static LogBookEntry&Proxy<LogBookEntry> last(string $sortedField = 'id')
 * @phpstan-method static LogBookEntry&Proxy<LogBookEntry> random(array $attributes = [])
 * @phpstan-method static LogBookEntry&Proxy<LogBookEntry> randomOrCreate(array $attributes = [])
 * @phpstan-method static ProxyRepositoryDecorator<LogBookEntry, EntityRepository> repository()
 * @phpstan-method static list<LogBookEntry&Proxy<LogBookEntry>> all()
 * @phpstan-method static list<LogBookEntry&Proxy<LogBookEntry>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<LogBookEntry&Proxy<LogBookEntry>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<LogBookEntry&Proxy<LogBookEntry>> findBy(array $attributes)
 * @phpstan-method static list<LogBookEntry&Proxy<LogBookEntry>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<LogBookEntry&Proxy<LogBookEntry>> randomSet(int $number, array $attributes = [])
 */
final class LogbookEntryFactory extends PersistentProxyObjectFactory
{
    public function withActiveCrew(int $number): self
    {
        return $this->with([
            'crewMembers' => UserFactory::new()->withAnnualActiveLicense()->many($number),
        ]);
    }

    public function withInactiveCrew(int $number): self
    {
        return $this->with([
            'crewMembers' => UserFactory::new()->withAnnualInactiveLicense()->many($number),
        ]);
    }

    public function finished(): self
    {
        return $this->with([
            'endAt' => new \DateTime('+1 hour'),
            'coveredDistance' => self::faker()->numberBetween(2, 20),
        ]);
    }

    public function notFinished(): self
    {
        return $this->with([
            'endAt' => null,
            'coveredDistance' => null,
        ]);
    }

    public function withDamages(): self
    {
        return $this->with([
            'shellDamages' => ShellDamageFactory::new()->many(1, 3),
        ]);
    }

    public function withoutDamages(): self
    {
        return $this->with([
            'shellDamages' => new ArrayCollection(),
        ]);
    }

    protected function defaults(): array|callable
    {
        $shell = ShellFactory::new();
        $finished = self::faker()->boolean();

        return [
            'shell' => $shell,
            'crewMembers' => UserFactory::new()->many(2),
            'endAt' => $finished ? new \DateTime('+1 hour') : null,
            'coveredDistance' => $finished ? self::faker()->numberBetween(2, 20) : null,
            'shellDamages' => ShellDamageFactory::new()->many(0, 3),
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this;
        // ->beforeInstantiate(function(LogbookEntry $logbookEntry) {})
    }

    public static function class(): string
    {
        return LogBookEntry::class;
    }
}
