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

namespace App\Tests\Service\Fit;

use App\Enum\SportType;
use App\Enum\TrainingSource;
use App\Factory\TrainingFactory;
use App\Factory\UploadedFileFactory;
use App\Factory\UserFactory;
use App\Service\FileUploader;
use App\Service\Fit\Exception\DuplicateFitFileException;
use App\Service\Fit\Exception\InvalidFitFileException;
use App\Service\Fit\FitTrainingImporter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\File;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class FitTrainingImporterTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private const string FIXTURES = __DIR__.'/../../../src/DataFixtures/Files/fit';

    public function testImportingAConcept2IntervalWorkoutStoresTheFileAndItsPhases(): void
    {
        $user = UserFactory::createOne();

        $training = $this->importer()->import($user, new File(self::FIXTURES.'/concept2-intervals.fit'), 4242);

        self::assertSame(TrainingSource::Fit, $training->getSource());
        self::assertSame(SportType::Ergometer, $training->getSport());
        self::assertSame(4242, $training->getConcept2Id());
        self::assertSame('Concept2', $training->getDevice());
        self::assertSame(25780, $training->getDuration());
        self::assertSame(7725, $training->getDistance());
        self::assertCount(3, $training->getTrainingPhases());
        self::assertTrue($training->getTrainingPhases()[1]->isRest());

        $fitFile = $training->getFitFile();
        self::assertNotNull($fitFile);
        self::assertSame(FileUploader::PRIVATE, $fitFile->getVisibility());
        self::assertStringEndsWith('.fit', $fitFile->getFilename());
        self::assertTrue(self::getContainer()->get(FileUploader::class)->exists($fitFile));
    }

    public function testTheSameSessionCannotBeImportedTwice(): void
    {
        $user = UserFactory::createOne();
        $file = new File(self::FIXTURES.'/polar-rowing.fit');
        $importer = $this->importer();

        $existing = $importer->import($user, $file);
        self::getContainer()->get('doctrine')->getManager()->persist($existing);
        self::getContainer()->get('doctrine')->getManager()->flush();

        try {
            $importer->import($user, $file);
            self::fail('Expected a DuplicateFitFileException.');
        } catch (DuplicateFitFileException $exception) {
            self::assertSame($existing->getId(), $exception->getExisting()->getId());
        }
    }

    public function testAnotherMemberCanImportTheSameFile(): void
    {
        $file = new File(self::FIXTURES.'/polar-rowing.fit');
        $importer = $this->importer();

        $first = $importer->import(UserFactory::createOne(), $file);
        self::getContainer()->get('doctrine')->getManager()->persist($first);
        self::getContainer()->get('doctrine')->getManager()->flush();

        $second = $importer->import(UserFactory::createOne(), $file);

        self::assertNotNull($second->getTrainedAt());
    }

    public function testAHandTypedSessionAtMidnightDoesNotBlockTheImport(): void
    {
        // A manual training sits at midnight; the FIT one carries the time of day.
        $user = UserFactory::createOne();
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('2026-08-29')]);

        $training = $this->importer()->import($user, new File(self::FIXTURES.'/polar-rowing.fit'));

        self::assertSame('2026-08-29', $training->getTrainedAt()->format('Y-m-d'));
    }

    public function testAnInvalidFileStoresNothing(): void
    {
        $user = UserFactory::createOne();

        try {
            $this->importer()->import($user, new File(self::FIXTURES.'/not-a-fit.fit'));
            self::fail('Expected an InvalidFitFileException.');
        } catch (InvalidFitFileException) {
        }

        TrainingFactory::repository()->assert()->count(0);
        UploadedFileFactory::repository()->assert()->count(0);
    }

    private function importer(): FitTrainingImporter
    {
        return self::getContainer()->get(FitTrainingImporter::class);
    }
}
