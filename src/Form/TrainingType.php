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

use App\Entity\Training;
use App\Form\Type\DurationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrainingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('trainedAt', DateTimeType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
            ])
            ->add('sport', ChoiceType::class, [
                'label' => 'Sport',
                'choices' => Training::getAvailableSports(),
                'placeholder' => '-- Sélectionner un sport --',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'entraînement',
                'choices' => Training::getAvailableTypes(),
                'group_by' => function ($choice) {
                    return match ($choice) {
                        Training::TYPE_B1, Training::TYPE_B2, Training::TYPE_REST, Training::TYPE_GENERALIZED_ENDURANCE => 'Aérobie',
                        Training::TYPE_B3, Training::TYPE_B4, Training::TYPE_B7, Training::TYPE_C2, Training::TYPE_SPLIT_LONG => 'Transition aérobie/anaérobie',
                        Training::TYPE_B5, Training::TYPE_SPLIT_SHORT => 'Anaérobie lactique',
                        Training::TYPE_B6, Training::TYPE_B8, Training::TYPE_C1 => 'Anaérobie alactique',
                        default => 'Autre',
                    };
                },
                'placeholder' => '-- Type d\'entraînement --',
                'required' => false,
            ])
            ->add('distance', NumberType::class, [
                'label' => 'Distance',
                'scale' => 1,
                'attr' => [
                    'step' => 0.1,
                    'placeholder' => 'En km',
                ],
                'html5' => true,
                'required' => false,
            ])
            ->add('duration', DurationType::class, [
                'label' => 'Durée',
            ])
            ->add('feeling', ChoiceType::class, [
                'label' => 'Sensation',
                'choices' => Training::getAvailableFeelings(),
                'placeholder' => 'Comment vous sentez-vous ?',
                'required' => false,
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Training::class,
        ]);
    }
}
