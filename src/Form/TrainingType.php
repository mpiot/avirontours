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
use App\Enum\Feeling;
use App\Enum\RatedPerceivedExertion;
use App\Enum\SportType;
use App\Form\DataTransformer\KilometersToMetersTransformer;
use App\Form\Type\DurationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrainingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sport', EnumType::class, [
                'label' => 'Sport',
                'class' => SportType::class,
                'choice_label' => 'label',
                'expanded' => true,
                'placeholder' => false,
                'block_prefix' => 'sport_choice',
            ])
            ->add('trainedAt', DateType::class, [
                'label' => 'Date de la séance',
                'widget' => 'single_text',
            ])
            ->add('duration', DurationType::class, [
                'label' => 'Durée',
            ])
            ->add('distance', NumberType::class, [
                'label' => 'Distance',
                'scale' => 1,
                'attr' => [
                    'step' => 0.1,
                ],
                'html5' => true,
                'required' => false,
            ])
            ->add('feeling', EnumType::class, [
                'label' => 'Sensation',
                'class' => Feeling::class,
                'choice_label' => 'label',
                'expanded' => true,
                'required' => false,
                'placeholder' => 'Non renseigné',
                'block_prefix' => 'feeling_choice',
            ])
            ->add('ratedPerceivedExertion', EnumType::class, [
                'label' => 'Effort perçu',
                'class' => RatedPerceivedExertion::class,
                'choice_label' => 'label',
                'expanded' => true,
                'required' => false,
                'placeholder' => 'Non renseigné',
                'block_prefix' => 'exertion_choice',
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
