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

namespace App\Command;

use App\Entity\PostalCode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:import:postal-code',
    description: 'Update the postal code database',
)]
class ImportPostalCodeCommand extends Command
{
    private const string DATASET_URL = 'https://www.data.gouv.fr/api/1/datasets/r/34d4364c-22eb-4ac0-b179-7a1845ac033a';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $response = $this->client->request('GET', self::DATASET_URL);
        $data = json_decode($response->getContent());

        // Init a progress bar
        $progressBar = new ProgressBar($output, \count($data));
        $progressBar->start();

        // Remove all
        $this->entityManager->createQuery('DELETE App\Entity\PostalCode')->execute();

        $seen = [];
        foreach ($data as $datum) {
            $key = "{$datum->codePostal}-{$datum->nomCommune}";
            if (isset($seen[$key])) {
                continue;
            }

            $postalCode = new PostalCode();
            $postalCode
                ->setPostalCode($datum->codePostal)
                ->setCity($datum->nomCommune)
            ;

            $this->entityManager->persist($postalCode);
            $progressBar->advance();

            $seen[$key] = true;
        }

        $this->entityManager->flush();
        $progressBar->finish();

        return Command::SUCCESS;
    }
}
