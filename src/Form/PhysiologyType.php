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

use App\Entity\Physiology;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhysiologyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('lightAerobicHeartRate', IntegerType::class, [
                'label' => 'B0 - UT2',
                'help' => '65-70% FCmax',
                'required' => false,
            ])
            ->add('heavyAerobicHeartRate', IntegerType::class, [
                'label' => 'B1 - UT1',
                'help' => '70-80% FCmax',
                'required' => false,
            ])
            ->add('anaerobicThresholdHeartRate', IntegerType::class, [
                'label' => 'B2 - AT',
                'help' => '80-85% FCmax',
                'required' => false,
            ])
            ->add('oxygenTransportationHeartRate', IntegerType::class, [
                'label' => 'B3 - TR',
                'help' => '85-95% FCmax',
                'required' => false,
            ])
            ->add('anaerobicHeartRate', IntegerType::class, [
                'label' => 'B5 - AN',
                'help' => '95-100% FCmax',
                'required' => false,
            ])
            ->add('maximumHeartRate', IntegerType::class, [
                'label' => 'FCmax',
                'required' => false,
            ])
            ->add('maximumOxygenConsumption', NumberType::class, [
                'label' => 'VO2max',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Physiology::class,
        ]);
    }
}
