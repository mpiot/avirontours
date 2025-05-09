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

use Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    // uncomment to reach your current PHP version
    // ->withPhpSets()
    ->withCodeQualityLevel(50)
    ->withCodingStyleLevel(25)
    ->withDeadCodeLevel(50)
    ->withTypeCoverageLevel(50)
    ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
    ->withSkip([
        SimplifyBoolIdenticalTrueRector::class,
    ])
;
