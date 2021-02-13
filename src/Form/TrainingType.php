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

namespace App\Form;

use App\Entity\Training;
use App\Form\Type\TrainingPhaseType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrainingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('trained_at', DateTimeType::class, [
                'label' => 'Date de l\'entraînement',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
            ])
            ->add('sport', ChoiceType::class, [
                'label' => 'Sport',
                'choices' => Training::getAvailableSports(),
                'placeholder' => '-- Sélectionner un sport --',
                'attr' => [
                    'data-controller' => 'select2',
                ],
            ])
            ->add('duration', DateIntervalType::class, [
                'label' => 'Durée',
                'labels' => [
                    'hours' => 'Heures',
                    'minutes' => 'Minutes',
                ],
                'with_years' => false,
                'with_months' => false,
                'with_days' => false,
                'with_weeks' => false,
                'with_hours' => true,
                'with_minutes' => true,
                'with_seconds' => false,
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
            ->add('feeling', ChoiceType::class, [
                'label' => 'Sensation',
                'choices' => Training::getAvailableFeelings(),
                'placeholder' => 'Comment vous sentez-vous ?',
                'required' => false,
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => [
                    'rows' => 1,
                ],
            ])
            ->add('trainingPhases', CollectionType::class, [
                'label' => 'Phases',
                'entry_type' => TrainingPhaseType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'attr' => [
                    'data-controller' => 'collection-type',
                    'data-collection-type-button-text-value' => 'Ajouter une phase',
                    'data-collection-type-label-value' => 'Phase n°',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Training::class,
        ]);
    }
}
