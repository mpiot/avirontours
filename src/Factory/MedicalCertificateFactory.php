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
use App\Enum\CertificateLevel;
use App\Enum\CertificateType;
use Symfony\Component\HttpFoundation\File\File;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<MedicalCertificate>
 */
final class MedicalCertificateFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'type' => self::faker()->randomElement(CertificateType::cases()),
            'level' => self::faker()->randomElement(CertificateLevel::cases()),
            'date' => self::faker()->dateTimeThisYear(),
            'uploadedFile' => UploadedFileFactory::new([
                'file' => new File(__DIR__.'/../DataFixtures/Files/document.pdf'),
            ])->private(),
        ];
    }

    #[\Override]
    protected function initialize(): static
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
