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

namespace App\Service\Fit;

use App\Entity\Training;
use App\Entity\User;
use App\Repository\TrainingRepository;
use App\Service\FileUploader;
use App\Service\Fit\Exception\DuplicateFitFileException;
use App\Service\Fit\Exception\InvalidFitFileException;
use App\Service\Fit\Exception\UnsupportedFitFileException;
use App\Service\TrimpCalculator;
use Symfony\Component\HttpFoundation\File\File;

/**
 * A FIT file into an unpersisted Training: read, map, refuse a duplicate, store the file, score the TRIMP.
 */
final readonly class FitTrainingImporter
{
    public function __construct(
        private FitActivityReader $reader,
        private FitActivityMapper $mapper,
        private TrimpCalculator $trimpCalculator,
        private FileUploader $fileUploader,
        private TrainingRepository $trainingRepository,
    ) {
    }

    /**
     * Returns an unpersisted Training with its phases and the stored FIT file; the caller flushes.
     *
     * @throws InvalidFitFileException     not a decodable FIT file
     * @throws UnsupportedFitFileException decodable but not an importable single-session activity
     * @throws DuplicateFitFileException   this member already has a training starting at that instant
     */
    public function import(User $user, File $file, ?int $concept2Id = null): Training
    {
        $activity = $this->reader->read($file);
        $training = $this->mapper->createTraining($user, $activity);

        $existing = $this->trainingRepository->findOneBy(['user' => $user, 'trainedAt' => $training->getTrainedAt()]);
        if (null !== $existing) {
            throw new DuplicateFitFileException($existing);
        }

        $uploadedFile = $this->fileUploader->upload($file, FileUploader::PRIVATE, 'fit');

        return $training
            ->setConcept2Id($concept2Id)
            ->setTrimp($this->trimpCalculator->compute($training))
            ->setFitFile($uploadedFile)
        ;
    }
}
