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

use App\Entity\MedicalCertificate;
use App\Repository\MedicalCertificateRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\File\File;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentProxyObjectFactory<MedicalCertificate>
 *
 * @method        MedicalCertificate|\Zenstruck\Foundry\Persistence\Proxy create(array|callable $attributes = [])
 * @method static MedicalCertificate|Proxy                                createOne(array $attributes = [])
 * @method static MedicalCertificate|Proxy                                find(object|array|mixed $criteria)
 * @method static MedicalCertificate|Proxy                                findOrCreate(array $attributes)
 * @method static MedicalCertificate|Proxy                                first(string $sortedField = 'id')
 * @method static MedicalCertificate|Proxy                                last(string $sortedField = 'id')
 * @method static MedicalCertificate|Proxy                                random(array $attributes = [])
 * @method static MedicalCertificate|Proxy                                randomOrCreate(array $attributes = [])
 * @method static MedicalCertificateRepository|ProxyRepositoryDecorator   repository()
 * @method static MedicalCertificate[]|Proxy[]                            all()
 * @method static MedicalCertificate[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static MedicalCertificate[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static MedicalCertificate[]|Proxy[]                            findBy(array $attributes)
 * @method static MedicalCertificate[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static MedicalCertificate[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        MedicalCertificate&Proxy<MedicalCertificate> create(array|callable $attributes = [])
 * @phpstan-method static MedicalCertificate&Proxy<MedicalCertificate> createOne(array $attributes = [])
 * @phpstan-method static MedicalCertificate&Proxy<MedicalCertificate> find(object|array|mixed $criteria)
 * @phpstan-method static MedicalCertificate&Proxy<MedicalCertificate> findOrCreate(array $attributes)
 * @phpstan-method static MedicalCertificate&Proxy<MedicalCertificate> first(string $sortedField = 'id')
 * @phpstan-method static MedicalCertificate&Proxy<MedicalCertificate> last(string $sortedField = 'id')
 * @phpstan-method static MedicalCertificate&Proxy<MedicalCertificate> random(array $attributes = [])
 * @phpstan-method static MedicalCertificate&Proxy<MedicalCertificate> randomOrCreate(array $attributes = [])
 * @phpstan-method static ProxyRepositoryDecorator<MedicalCertificate, EntityRepository> repository()
 * @phpstan-method static list<MedicalCertificate&Proxy<MedicalCertificate>> all()
 * @phpstan-method static list<MedicalCertificate&Proxy<MedicalCertificate>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<MedicalCertificate&Proxy<MedicalCertificate>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<MedicalCertificate&Proxy<MedicalCertificate>> findBy(array $attributes)
 * @phpstan-method static list<MedicalCertificate&Proxy<MedicalCertificate>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<MedicalCertificate&Proxy<MedicalCertificate>> randomSet(int $number, array $attributes = [])
 */
final class MedicalCertificateFactory extends PersistentProxyObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'type' => self::faker()->randomElement(MedicalCertificate::getAvailableTypes()),
            'level' => self::faker()->randomElement(MedicalCertificate::getAvailableLevels()),
            'date' => self::faker()->dateTimeThisYear(),
            'uploadedFile' => UploadedFileFactory::new([
                'file' => new File(__DIR__.'/../DataFixtures/Files/document.pdf'),
            ])->private(),
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this;
        // ->beforeInstantiate(function(MedicalCertificate $medicalCertificate) {})
    }

    public static function class(): string
    {
        return MedicalCertificate::class;
    }
}
