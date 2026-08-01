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

use App\Entity\UploadedFile;
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\File\File;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<UploadedFile>
 */
final class UploadedFileFactory extends PersistentObjectFactory
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
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(UploadFile $uploadFile): void {})
            ->instantiateWith(Instantiator::withoutConstructor()->allowExtra('file'))
            ->afterInstantiate(function (UploadedFile $uploadedFile, array $attributes): void {
                $uploadedFileReference = $this->fileUploader->upload($attributes['file'], $uploadedFile->getVisibility());

                $uploadedFile
                    ->setFilename($uploadedFileReference->getFilename())
                    ->setMimeType($uploadedFileReference->getMimeType())
                    ->setOriginalFilename($uploadedFileReference->getOriginalFilename())
                    ->setVisibility($uploadedFileReference->getVisibility())
                    ->setCreatedAt($attributes['createdAt'])
                    ->setUpdatedAt($attributes['updatedAt'])
                ;

                unset($uploadedFileReference);
            })
        ;
    }

    public static function class(): string
    {
        return UploadedFile::class;
    }
}
