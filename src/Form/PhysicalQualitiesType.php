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

use App\Entity\PhysicalQualities;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhysicalQualitiesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('proprioception', IntegerType::class, [
                'label' => 'Proprioception',
                'help' => 'Note entre 0 et 20',
            ])
            ->add('weightPowerRatio', IntegerType::class, [
                'label' => 'Ratio poids/puissance',
                'help' => 'Note entre 0 et 20',
            ])
            ->add('explosiveStrength', IntegerType::class, [
                'label' => 'Force explosive',
                'help' => 'Note entre 0 et 20',
            ])
            ->add('enduranceStrength', IntegerType::class, [
                'label' => 'Force d\'endurance',
                'help' => 'Note entre 0 et 20',
            ])
            ->add('maximumStrength', IntegerType::class, [
                'label' => 'Force maximum',
                'help' => 'Note entre 0 et 20',
            ])
            ->add('stressResistance', IntegerType::class, [
                'label' => 'Résistance',
                'help' => 'Note entre 0 et 20',
            ])
            ->add('coreStrength', IntegerType::class, [
                'label' => 'Gainage',
                'help' => 'Note entre 0 et 20',
            ])
            ->add('flexibility', IntegerType::class, [
                'label' => 'Souplesse',
                'help' => 'Note entre 0 et 20',
            ])
            ->add('recovery', IntegerType::class, [
                'label' => 'Récupération',
                'help' => 'Note entre 0 et 20',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PhysicalQualities::class,
        ]);
    }
}
