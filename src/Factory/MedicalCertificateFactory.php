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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @method static                   MedicalCertificate|Proxy createOne(array $attributes = [])
 * @method static                   MedicalCertificate[]|Proxy[] createMany(int $number, $attributes = [])
 * @method static                   MedicalCertificate|Proxy findOrCreate(array $attributes)
 * @method static                   MedicalCertificate|Proxy random(array $attributes = [])
 * @method static                   MedicalCertificate|Proxy randomOrCreate(array $attributes = [])
 * @method static                   MedicalCertificate[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static                   MedicalCertificate[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static                   MedicalCertificateRepository|RepositoryProxy repository()
 * @method MedicalCertificate|Proxy create($attributes = [])
 */
final class MedicalCertificateFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'type' => self::faker()->randomElement(MedicalCertificate::getAvailableTypes()),
            'level' => self::faker()->randomElement(MedicalCertificate::getAvailableLevels()),
            'date' => self::faker()->dateTimeThisYear(),
            'file' => $this->getUploadedFile(),
        ];
    }

    private function getUploadedFile(): UploadedFile
    {
        $file = 'medical-certificate.pdf';
        $fs = new Filesystem();
        $targetPath = sys_get_temp_dir().'/'.$file;
        $fs->copy(__DIR__.'/Files/'.$file, $targetPath, true);

        return new UploadedFile(
            $targetPath,
            'my-certificate.pdf',
            'application/pdf',
            null,
            true
        );
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this
            // ->beforeInstantiate(function(MedicalCertificate $medicalCertificate) {})
        ;
    }

    protected static function getClass(): string
    {
        return MedicalCertificate::class;
    }
}
