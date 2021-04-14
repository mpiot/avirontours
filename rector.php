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

use Rector\Core\Configuration\Option;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    // $parameters = $containerConfigurator->parameters();

    // Define what rule sets will be applied
    // $parameters->set(Option::SETS, [
    //     SetList::DEAD_CODE,
    // ]);

    // get services (needed for register a single rule)
    $services = $containerConfigurator->services();

    // register a single rule
    $services->set(TypedPropertyRector::class);

    $services->set(\Rector\Php80\Rector\Class_\AnnotationToAttributeRector::class)
        ->call('configure', [[
            \Rector\Php80\Rector\Class_\AnnotationToAttributeRector::ANNOTATION_TO_ATTRIBUTE => \Symplify\SymfonyPhpConfig\ValueObjectInliner::inline([
                new \Rector\Php80\ValueObject\AnnotationToAttribute(
                    'Symfony\Component\Validator\Constraints\LessThanOrEqual',
                    'Symfony\Component\Validator\Constraints\LessThanOrEqual'
                ),
            ]),
        ]])
    ;
};
