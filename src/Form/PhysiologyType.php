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

use App\Entity\Physiology;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhysiologyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lightAerobicHeartRateMin', IntegerType::class, [
                'label' => 'B0 - UT2',
                'help' => '65-70% FCmax',
            ])
            ->add('heavyAerobicHeartRateMin', IntegerType::class, [
                'label' => 'B1 - UT1',
                'help' => '70-80% FCmax',
            ])
            ->add('anaerobicThresholdHeartRateMin', IntegerType::class, [
                'label' => 'B2 - AT',
                'help' => '80-85% FCmax',
            ])
            ->add('oxygenTransportationHeartRateMin', IntegerType::class, [
                'label' => 'B3 - TR',
                'help' => '85-95% FCmax',
            ])
            ->add('anaerobicHeartRateMin', IntegerType::class, [
                'label' => 'B5 - AN',
                'help' => '95-100% FCmax',
            ])
            ->add('maximumHeartRate', IntegerType::class, [
                'label' => 'FCmax',
            ])
            ->add('maximumOxygenConsumption', NumberType::class, [
                'label' => 'VO2max',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Physiology::class,
        ]);
    }
}
