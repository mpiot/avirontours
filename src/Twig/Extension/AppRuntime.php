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

namespace App\Twig\Extension;

use App\Util\BarcodeGenerator;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Twig\Extension\RuntimeExtensionInterface;

class AppRuntime implements RuntimeExtensionInterface, ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $locator,
        private readonly string $publicDir,
    ) {
    }

    public function generateQrCode(string $text, int $width = -1, int $height = -1): string
    {
        return BarcodeGenerator::qrCode($text, $width, $height);
    }

    public function getEncoreEntryCssSource(string $entryName): string
    {
        $files = $this->locator
            ->get(EntrypointLookupInterface::class)
            ->getCssFiles($entryName)
        ;

        $source = '';
        foreach ($files as $file) {
            $source .= file_get_contents($this->publicDir.$file);
        }

        return $source;
    }

    public static function getSubscribedServices(): array
    {
        return [
            EntrypointLookupInterface::class,
        ];
    }
}
