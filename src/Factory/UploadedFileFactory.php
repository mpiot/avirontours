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

namespace App\Factory;

use App\Entity\UploadedFile;
use App\Repository\UploadedFileRepository;
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\File\File;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends PersistentProxyObjectFactory<UploadedFile>
 *
 * @method        UploadedFile|Proxy                     create(array|callable $attributes = [])
 * @method static UploadedFile|Proxy                     createOne(array $attributes = [])
 * @method static UploadedFile|Proxy                     find(object|array|mixed $criteria)
 * @method static UploadedFile|Proxy                     findOrCreate(array $attributes)
 * @method static UploadedFile|Proxy                     first(string $sortedField = 'id')
 * @method static UploadedFile|Proxy                     last(string $sortedField = 'id')
 * @method static UploadedFile|Proxy                     random(array $attributes = [])
 * @method static UploadedFile|Proxy                     randomOrCreate(array $attributes = [])
 * @method static UploadedFileRepository|RepositoryProxy repository()
 * @method static UploadedFile[]|Proxy[]                 all()
 * @method static UploadedFile[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static UploadedFile[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static UploadedFile[]|Proxy[]                 findBy(array $attributes)
 * @method static UploadedFile[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static UploadedFile[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<UploadedFile> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<UploadedFile> createOne(array $attributes = [])
 * @phpstan-method static Proxy<UploadedFile> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<UploadedFile> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<UploadedFile> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<UploadedFile> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<UploadedFile> random(array $attributes = [])
 * @phpstan-method static Proxy<UploadedFile> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<UploadedFile> repository()
 * @phpstan-method static list<Proxy<UploadedFile>> all()
 * @phpstan-method static list<Proxy<UploadedFile>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<UploadedFile>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<UploadedFile>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<UploadedFile>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<UploadedFile>> randomSet(int $number, array $attributes = [])
 */
final class UploadedFileFactory extends PersistentProxyObjectFactory
{
    public function __construct(private readonly FileUploader $fileUploader)
    {
        parent::__construct();
    }

    public function pdf(): static
    {
        return $this->with([
            'file' => new File(__DIR__.'/../DataFixtures/Files/document.pdf'),
        ]);
    }

    public function png(): static
    {
        return $this->with([
            'file' => new File(__DIR__.'/../DataFixtures/Files/placeholder_1000x1000.png'),
        ]);
    }

    public function public(): static
    {
        return $this->with([
            'visibility' => FileUploader::PUBLIC,
        ]);
    }

    public function private(): static
    {
        return $this->with([
            'visibility' => FileUploader::PRIVATE,
        ]);
    }

    protected function defaults(): array|callable
    {
        return [
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(UploadFile $uploadFile): void {})
            ->instantiateWith(Instantiator::withoutConstructor()->allowExtra('file'))
            ->afterInstantiate(function (UploadedFile &$uploadedFile, array $attributes) {
                $uploadedFile = $this->fileUploader->upload($attributes['file'], $uploadedFile->getVisibility());
                $uploadedFile
                    ->setCreatedAt($attributes['createdAt'])
                    ->setUpdatedAt($attributes['updatedAt'])
                ;
            })
        ;
    }

    public static function class(): string
    {
        return UploadedFile::class;
    }
}
