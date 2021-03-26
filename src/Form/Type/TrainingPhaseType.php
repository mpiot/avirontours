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

namespace App\Form\Type;

use App\Entity\TrainingPhase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrainingPhaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'required' => false,
            ])
            ->add('intensity', ChoiceType::class, [
                'label' => 'Intensité',
                'choices' => TrainingPhase::getAvailableIntensities(),
                'placeholder' => '-- Sélectionner une intensité --',
            ])
            ->add('duration', DateIntervalType::class, [
                'label' => 'Durée',
                'labels' => [
                    'hours' => 'Heures',
                    'minutes' => 'Minutes',
                    'seconds' => 'Secondes',
                ],
                'with_years' => false,
                'with_months' => false,
                'with_days' => false,
                'with_weeks' => false,
                'with_hours' => true,
                'with_minutes' => true,
                'with_seconds' => true,
                'attr' => [
                    'data-controller' => 'select2',
                ],
            ])
            ->add('distance', NumberType::class, [
                'label' => 'Distance',
                'scale' => 1,
                'attr' => [
                    'step' => 0.1,
                    'placeholder' => 'En Km',
                ],
                'html5' => true,
                'required' => false,
            ])
            ->add('split', TextType::class, [
                'label' => 'Split',
                'required' => false,
                'attr' => [
                    'placeholder' => '0:00.0',
                ],
            ])
            ->add('spm', IntegerType::class, [
                'label' => 'Cadence',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TrainingPhase::class,
        ]);
    }
}
