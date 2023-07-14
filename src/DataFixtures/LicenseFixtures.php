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

namespace App\DataFixtures;

use App\Entity\License;
use App\Entity\MedicalCertificate;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class LicenseFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getSeasonData() as [$seasonCategory, $user, $certificateType, $certificateLevel, $isValid]) {
            $license = new License($seasonCategory);
            $license
                ->setUser($user)
                ->setMedicalCertificate(
                    (new MedicalCertificate())
                        ->setType($certificateType)
                        ->setLevel($certificateLevel)
                        ->setDate(new \DateTime('-3 months'))
                        ->setFile($this->getUploadedFile())
                )
            ;

            if ($isValid) {
                $license->setMarking(['validated' => 1]);
            }

            $manager->persist($license);
            $this->addReference($seasonCategory->getSeason()->getName().'-'.$seasonCategory->getName().'-'.$user->getFullName(), $license);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            SeasonFixtures::class,
            UserFixtures::class,
        ];
    }

    private function getSeasonData(): array
    {
        return [
            [$this->getReference('2018-Adulte'), $this->getReference('on-water.user'), MedicalCertificate::TYPE_CERTIFICATE, MedicalCertificate::LEVEL_COMPETITION, true],
            [$this->getReference('2018-Adulte'), $this->getReference('outdated.user'), MedicalCertificate::TYPE_CERTIFICATE, MedicalCertificate::LEVEL_PRACTICE, true],
            [$this->getReference('2018-Indoor'), $this->getReference('indoor.user'), MedicalCertificate::TYPE_CERTIFICATE, MedicalCertificate::LEVEL_PRACTICE, true],
            [$this->getReference('2019-Adulte'), $this->getReference('on-water.user'), MedicalCertificate::TYPE_ATTESTATION, MedicalCertificate::LEVEL_COMPETITION, true],
            [$this->getReference('2019-Adulte'), $this->getReference('outdated.user'), MedicalCertificate::TYPE_ATTESTATION, MedicalCertificate::LEVEL_PRACTICE, false],
            [$this->getReference('2019-Indoor'), $this->getReference('indoor.user'), MedicalCertificate::TYPE_ATTESTATION, MedicalCertificate::LEVEL_PRACTICE, true],
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
}
