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

use App\Factory\LicenseFactory;
use App\Factory\UploadedFileFactory;
use App\Service\FileUploader;
use App\Tests\AppWebTestCase;

class UploadedFileRemoverTest extends AppWebTestCase
{
    public function testTheCascadedFileIsRemovedOnlyOnFlush(): void
    {
        self::ensureKernelShutdown();
        static::createClient();
        $entityManager = self::getEntityManager();
        $fileUploader = self::getContainer()->get(FileUploader::class);

        $license = LicenseFactory::createOne();
        $path = $fileUploader->getAbsolutePath($license->getMedicalCertificate()->getUploadedFile());

        $this->assertFileExists($path);

        $entityManager->remove($license);

        $this->assertFileExists($path);

        $entityManager->flush();

        $this->assertFileDoesNotExist($path);
    }

    public function testTheReplacedFileIsRemovedOnlyOnFlush(): void
    {
        self::ensureKernelShutdown();
        static::createClient();
        $entityManager = self::getEntityManager();
        $fileUploader = self::getContainer()->get(FileUploader::class);

        $license = LicenseFactory::createOne();
        $medicalCertificate = $license->getMedicalCertificate();
        $oldPath = $fileUploader->getAbsolutePath($medicalCertificate->getUploadedFile());

        $medicalCertificate->setUploadedFile(UploadedFileFactory::new()->pdf()->private()->create());

        $this->assertFileExists($oldPath);

        $entityManager->flush();

        $this->assertFileDoesNotExist($oldPath);
    }
}
