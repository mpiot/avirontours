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

namespace App\Form;

use App\Enum\DistanceUnit;
use App\Form\DataTransformer\KilometersToMetersTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A distance stored in meters, entered in the unit given as option.
 */
class DistanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (DistanceUnit::Kilometers === $options['unit']) {
            $builder->addModelTransformer(new KilometersToMetersTransformer());

            return;
        }

        // NumberType hands back a float, the entity stores whole meters.
        $builder->addModelTransformer(new CallbackTransformer(
            static fn (?int $meters): ?int => $meters,
            static fn (int|float|null $value): ?int => null === $value ? null : (int) round($value),
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['unit'] = $options['unit'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->define('unit')
            ->required()
            ->allowedTypes(DistanceUnit::class)
        ;

        $resolver
            ->setDefaults([
                'html5' => true,
                'scale' => static fn (Options $options): int => DistanceUnit::Kilometers === $options['unit'] ? 1 : 0,
                'attr' => static fn (Options $options): array => DistanceUnit::Kilometers === $options['unit']
                    ? ['step' => 0.1, 'inputmode' => 'decimal']
                    : ['inputmode' => 'numeric'],
            ]);
    }

    public function getParent(): string
    {
        return NumberType::class;
    }
}
