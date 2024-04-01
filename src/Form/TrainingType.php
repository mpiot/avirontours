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
use App\Enum\EnergyPathwayType;
use App\Enum\SportType;
use App\Form\DataTransformer\KilometersToMetersTransformer;
use App\Form\Type\DurationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
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
            ->add('sport', EnumType::class, [
                'label' => 'Sport',
                'class' => SportType::class,
                'choice_label' => 'label',
                'placeholder' => '-- Sélectionner un sport --',
            ])
            ->add('type', EnumType::class, [
                'label' => 'Type d\'entraînement',
                'class' => \App\Enum\TrainingType::class,
                'group_by' => function (\App\Enum\TrainingType $choice) {
                    return EnergyPathwayType::fromTrainingType($choice)->label();
                },
                'choice_label' => 'label',
                'placeholder' => '-- Type d\'entraînement --',
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
            ->add('feeling', RangeType::class, [
                'label' => 'Comment vous sentez-vous ?',
                'attr' => [
                    'min' => 0,
                    'max' => 1,
                    'step' => 0.1,
                ],
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
            ])
        ;

        $builder->get('distance')->addModelTransformer(new KilometersToMetersTransformer());

        $data = $builder->getData();
        \assert($data instanceof Training);

        // If there is TrainingPhases, then, the Training is sync
        if (false === $data->getTrainingPhases()->isEmpty()) {
            $builder->get('trainedAt')->setDisabled(true);
            $builder->get('sport')->setDisabled(true);
            $builder->get('distance')->setDisabled(true);
            $builder->get('duration')->setDisabled(true);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Training::class,
        ]);
    }
}
