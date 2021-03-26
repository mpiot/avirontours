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

use App\Entity\WorkoutMaximumLoad;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkoutMaximumLoadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rowingTirage', IntegerType::class, [
                'label' => 'Tirade rowing',
                'help' => 'En kg',
            ])
            ->add('benchPress', IntegerType::class, [
                'label' => 'Développé couché',
                'help' => 'En kg',
            ])
            ->add('squat', IntegerType::class, [
                'label' => 'Squat',
                'help' => 'En kg',
            ])
            ->add('legPress', IntegerType::class, [
                'label' => 'Presse',
                'help' => 'En kg',
            ])
            ->add('clean', IntegerType::class, [
                'label' => 'Epaulé',
                'help' => 'En kg',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkoutMaximumLoad::class,
        ]);
    }
}
