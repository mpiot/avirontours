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
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @extends PersistentProxyObjectFactory<LogbookEntry>
 *
 * @method        LogbookEntry|\Zenstruck\Foundry\Persistence\Proxy create(array|callable $attributes = [])
 * @method static LogbookEntry|Proxy                                createOne(array $attributes = [])
 * @method static LogbookEntry|Proxy                                find(object|array|mixed $criteria)
 * @method static LogbookEntry|Proxy                                findOrCreate(array $attributes)
 * @method static LogbookEntry|Proxy                                first(string $sortedField = 'id')
 * @method static LogbookEntry|Proxy                                last(string $sortedField = 'id')
 * @method static LogbookEntry|Proxy                                random(array $attributes = [])
 * @method static LogbookEntry|Proxy                                randomOrCreate(array $attributes = [])
 * @method static LogbookEntryRepository|ProxyRepositoryDecorator   repository()
 * @method static LogbookEntry[]|Proxy[]                            all()
 * @method static LogbookEntry[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static LogbookEntry[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static LogbookEntry[]|Proxy[]                            findBy(array $attributes)
 * @method static LogbookEntry[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static LogbookEntry[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<LogbookEntry> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<LogbookEntry> createOne(array $attributes = [])
 * @phpstan-method static Proxy<LogbookEntry> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<LogbookEntry> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<LogbookEntry> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<LogbookEntry> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<LogbookEntry> random(array $attributes = [])
 * @phpstan-method static Proxy<LogbookEntry> randomOrCreate(array $attributes = [])
 * @phpstan-method static ProxyRepositoryDecorator<LogbookEntry> repository()
 * @phpstan-method static list<Proxy<LogbookEntry>> all()
 * @phpstan-method static list<Proxy<LogbookEntry>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<LogbookEntry>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<LogbookEntry>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<LogbookEntry>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<LogbookEntry>> randomSet(int $number, array $attributes = [])
 */
final class LogbookEntryFactory extends PersistentProxyObjectFactory
{
    public function withActiveCrew(int $number): self
    {
        return $this->with([
            'crewMembers' => UserFactory::new()->withValidAnnualActiveLicense()->many($number),
        ]);
    }

    public function withInactiveCrew(int $number): self
    {
        return $this->with([
            'crewMembers' => UserFactory::new()->withValidAnnualInactiveLicense()->many($number),
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
            'shellDamages' => ShellDamageFactory::new()->many(1),
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
            'shellDamages' => ShellDamageFactory::new()->many(1),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this;
        // ->beforeInstantiate(function(LogbookEntry $logbookEntry) {})
    }

    public static function class(): string
    {
        return LogbookEntry::class;
    }
}
