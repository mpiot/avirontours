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

namespace App\Service;

use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;

use function Symfony\Component\String\u;

class PdfHelper implements ServiceSubscriberInterface
{
    private const CHROME_BINARY = 'google-chrome';

    public function __construct(
        private readonly ContainerInterface $locator,
        private readonly string $projectDir,
    ) {
    }

    public function twigToPdf(string $view, array $parameters = [], string $outFile = null): string
    {
        $content = $this->locator->get('twig')->render($view, $parameters);

        return $this->htmlToPdf($content, $outFile);
    }

    public function htmlToPdf(string $content, string $outFile = null): string
    {
        // Prepare the HTML content:
        // 1. use absolute local path for assets
        // 2. remove integrity checks
        $content = u($content)
            ->replace('/build', "{$this->projectDir}/public/build")
            ->replaceMatches('#integrity="[\w\-\+\/]+"#', '')
            ->toString()
        ;

        // Create an .html file with the content
        $filesystem = new Filesystem();
        $tmpFile = $filesystem->tempnam(sys_get_temp_dir(), 'pdf_', '.html');
        $filesystem->appendToFile($tmpFile, $content);

        // Generate the .pdf file
        $outFile = $outFile ?? $filesystem->tempnam(sys_get_temp_dir(), 'pdf_', '.pdf');
        $process = new Process([
            self::CHROME_BINARY,
            '--headless',
            '--disable-gpu',
            '--print-to-pdf-no-header',
            "--print-to-pdf={$outFile}",
            $tmpFile,
        ]);
        $process->run();

        // Remove the .html file
        $filesystem->remove($tmpFile);

        if (false === $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $outFile;
    }

    public static function getSubscribedServices(): array
    {
        return [
            'twig' => Environment::class,
        ];
    }
}
