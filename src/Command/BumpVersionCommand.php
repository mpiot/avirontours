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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

use function Symfony\Component\String\u;

#[AsCommand(
    name: 'app:bump-version',
    description: 'Bump project version',
)]
class BumpVersionCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('version', InputArgument::OPTIONAL, 'The new version')
            ->addOption('meta', null, InputOption::VALUE_NONE, 'The version is a meta (placed after existing version)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();

        $version = $input->getArgument('version');
        $filenames = ['config/services.yaml', 'sonar-project.properties'];

        foreach ($filenames as $filename) {
            $fileContent = file_get_contents($filename);

            if (true === $input->getOption('meta')) {
                $fileContent = u($fileContent)
                    ->replaceMatches('#((?:app_version|sonar\.projectVersion)(?:(?::|=)\s?))(\'?)([0-9a-z\.\-\+]+)(\'?)#', '${1}${2}${3}+'.$version.'${4}')
                    ->toString()
                ;
            } else {
                $fileContent = u($fileContent)
                    ->replaceMatches('#((?:app_version|sonar\.projectVersion)(?:(?::|=)\s?))(\'?)([0-9a-z\.\-\+]+)(\'?)#', '${1}${2}'.$version.'${4}')
                    ->toString()
                ;
            }

            $filesystem->dumpFile($filename, $fileContent);
        }

        if (true === $input->getOption('meta')) {
            $message = sprintf('The meta %s has been successfully added after the version.', $version);
        } else {
            $message = sprintf('The version has been successfully updated to %s.', $version);
        }

        $io->success($message);

        return Command::SUCCESS;
    }
}
