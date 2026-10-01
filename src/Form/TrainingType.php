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
use App\Form\DataTransformer\DateIntervalToTenthSecondsTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class TrainingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $training = $options['data'];
        \assert($training instanceof Training);

        // A synced training keeps what the ergometer measured.
        $locked = false === $training->getTrainingPhases()->isEmpty();

        $builder = new DynamicFormBuilder($builder);
        $builder
            ->add('sport', EnumType::class, [
                'label' => 'Sport',
                'class' => SportType::class,
                'choice_label' => 'label',
                'expanded' => true,
                'placeholder' => false,
                'block_prefix' => 'sport_choice',
                'disabled' => $locked,
            ])
            ->add('trainedAt', DateType::class, [
                'label' => 'Date de la séance',
                'widget' => 'single_text',
                'disabled' => $locked,
            ])
            ->add('duration', DateIntervalType::class, [
                'label' => 'Durée',
                'block_prefix' => 'duration',
                'with_years' => false,
                'with_months' => false,
                'with_days' => false,
                'with_hours' => true,
                'with_minutes' => true,
                'with_seconds' => true,
                'hours' => range(0, 23),
                'minutes' => range(0, 59),
                'seconds' => range(0, 59),
                'disabled' => $locked,
            ])
            ->add('averageHeartRate', IntegerType::class, [
                'label' => 'FC moyenne',
                'required' => false,
                'disabled' => $locked,
            ])
            ->add('maxHeartRate', IntegerType::class, [
                'label' => 'FC max',
                'required' => false,
                'disabled' => $locked,
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

        $builder->addDependent('distance', 'sport', static function (DependentField $field, ?SportType $sport) use ($locked): void {
            $unit = $sport?->distanceUnit();
            if (null === $unit) {
                return;
            }

            $field->add(DistanceType::class, [
                'label' => 'Distance',
                'required' => false,
                'disabled' => $locked,
                'unit' => $unit,
            ]);
        });

        $builder->get('duration')->addModelTransformer(new DateIntervalToTenthSecondsTransformer());

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
    }

    // A sport without distance clears the stale value: the removed field no longer maps it.
    public function onSubmit(SubmitEvent $event): void
    {
        $training = $event->getData();
        if (null === $training->getSport()?->distanceUnit()) {
            $training->setDistance(null);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Training::class,
        ]);
    }
}
