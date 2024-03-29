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

use App\Entity\Shell;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShellType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('numberRowers', IntegerType::class, [
                'label' => 'Nombre de rameurs',
            ])
            ->add('rowingType', ChoiceType::class, [
                'label' => 'Type',
                'choices' => Shell::getAvailableRowingTypes(),
                'expanded' => true,
                'label_attr' => ['class' => 'radio-custom'],
            ])
            ->add('coxed', CheckboxType::class, [
                'label' => 'Barré',
                'label_attr' => ['class' => 'switch-custom'],
                'required' => false,
            ])
            ->add('yolette', CheckboxType::class, [
                'label' => 'Yolette',
                'label_attr' => ['class' => 'switch-custom'],
                'required' => false,
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'Actif',
                'label_attr' => ['class' => 'switch-custom'],
                'required' => false,
            ])
            ->add('personalBoat', CheckboxType::class, [
                'label' => 'Bâteau perso',
                'label_attr' => ['class' => 'switch-custom'],
                'required' => false,
            ])
            ->add('productionYear', NumberType::class, [
                'label' => 'Année de fabrication',
                'html5' => true,
                'required' => false,
            ])
            ->add('weightCategory', ChoiceType::class, [
                'label' => 'Catégorie porteur',
                'choices' => Shell::getAvailableWeightCategories(),
                'required' => false,
            ])
            ->add('newPrice', MoneyType::class, [
                'label' => 'Prix neuf',
                'required' => false,
            ])
            ->add('mileage', NumberType::class, [
                'label' => 'Distance parcourue',
                'html5' => true,
                'attr' => [
                    'step' => 0.1,
                ],
                'required' => false,
            ])
            ->add('riggerMaterial', ChoiceType::class, [
                'label' => 'Matériaux portants',
                'choices' => Shell::getAvailableRiggerMaterials(),
                'placeholder' => 'N/A',
                'required' => false,
                'expanded' => true,
                'label_attr' => ['class' => 'radio-custom'],
            ])
            ->add('riggerPosition', ChoiceType::class, [
                'label' => 'Position portants',
                'choices' => Shell::getAvailableRiggerPositions(),
                'placeholder' => 'N/A',
                'required' => false,
                'expanded' => true,
                'label_attr' => ['class' => 'radio-custom'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Shell::class,
        ]);
    }
}
