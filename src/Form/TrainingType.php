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
use App\Enum\SportType;
use App\Form\DataTransformer\KilometersToMetersTransformer;
use App\Form\Type\DurationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
                'placeholder' => '-- Sélectionner un sport --',
            ])
            ->add('trainedAt', DateType::class, [
                'label' => 'Début de la séance',
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
                    'placeholder' => 'km',
                ],
                'html5' => true,
                'required' => false,
            ])
            ->add('feeling', ChoiceType::class, [
                'label' => 'Comment vous sentez-vous ?',
                'choices' => [
                    '<span class="far fa-face-tired text-danger me-3"></span> Horrible' => 0.0,
                    '<span class="far fa-frown text-warning me-3"></span> Mal' => 0.25,
                    '<span class="far fa-face-meh text-warning me-3"></span> Moyen' => 0.50,
                    '<span class="far fa-smile text-success me-3"></span> Bien' => 0.75,
                    '<span class="far fa-face-grin-wide text-success me-3"></span> Très bien' => 1.0,
                ],
                'options_as_html' => true,
                'autocomplete' => true,
                'placeholder' => '-- Sélectionner un choix --',
            ])
            ->add('ratedPerceivedExertion', ChoiceType::class, [
                'label' => 'RPE - Perception de l\'effort',
                'choices' => [
                    'N/A' => null,
                    '<span class="d-block" style="background-color: rgba(86, 233, 233, .5);">1 - Très très facile</span>' => 1,
                    '<span class="d-block" style="background-color: rgba(86, 233, 170, .5);">2 - 😁 Facile</span>' => 2,
                    '<span class="d-block" style="background-color: rgba(86, 233, 100, .5);">3 - Modéré</span>' => 3,
                    '<span class="d-block" style="background-color: rgba(126, 211, 33, .5);">4 - 😐 Assez dur</span>' => 4,
                    '<span class="d-block" style="background-color: rgba(255, 250, 45, .5);">5 - Dur</span>' => 5,
                    '<span class="d-block" style="background-color: rgba(255, 200, 45, .5);">6 - 😕 Vraiment dur</span>' => 6,
                    '<span class="d-block" style="background-color: rgba(255, 150, 45, .5);">7 - Très dur</span>' => 7,
                    '<span class="d-block" style="background-color: rgba(255, 100, 45, .5);">8 - 😣 Extrêmement dur</span>' => 8,
                    '<span class="d-block" style="background-color: rgba(210, 50, 50, .5);">9 - Presque maximal</span>' => 9,
                    '<span class="d-block" style="background-color: rgba(210, 0, 50, .5);">10 - 😖 Maximal</span>' => 10,
                ],
                'options_as_html' => true,
                'autocomplete' => true,
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
