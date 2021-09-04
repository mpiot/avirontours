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
                'attr' => [
                    'data-controller' => 'select2',
                ],
            ])
            ->add('energyPathway', ChoiceType::class, [
                'label' => 'Filère énergétique',
                'choices' => Training::getAvailableEnergyPathways(),
                'placeholder' => '-- Filère énergétique --',
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
