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

namespace App\Command;

use App\Entity\PostalCode;
use App\Repository\PostalCodeRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:import:postal-code',
    description: 'Update the postal code database',
)]
class ImportPostalCodeCommand extends Command
{
    private const DATASET_URL = 'https://www.data.gouv.fr/fr/datasets/r/5ed9b092-a25d-49e7-bdae-0152797c7577';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly PostalCodeRepository $repository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $this->downloadResource();

        // Count number of lines
        $file->seek(\PHP_INT_MAX);
        $nbLines = $file->key();
        $file->rewind();

        // Init a progress bar
        $progressBar = new ProgressBar($output, $nbLines);
        $progressBar->start();

        // Skip  the first line
        $file->fgetcsv();

        // Import postal codes
        while (false !== $data = $file->fgetcsv(separator: ';')) {
            if (\array_key_exists(2, $data) && false === $this->repository->exists($data[2], $data[3])) {
                $postalCode = new PostalCode();
                $postalCode
                    ->setPostalCode($data[2])
                    ->setCity($data[3])
                ;

                $this->repository->save($postalCode, true);
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        return Command::SUCCESS;
    }

    private function downloadResource(): \SplFileObject
    {
        $filesystem = new Filesystem();
        $filename = $filesystem->tempnam(sys_get_temp_dir(), 'import-postal-code');

        $response = $this->client->request('GET', self::DATASET_URL);
        $fileHandler = fopen($filename, 'w');
        foreach ($this->client->stream($response) as $chunk) {
            fwrite($fileHandler, $chunk->getContent());
        }
        fclose($fileHandler);

        return new \SplFileObject($filename, 'r');
    }
}
