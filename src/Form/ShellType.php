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

use App\Entity\Member;
use App\Entity\Shell;
use App\Service\ShellAbbreviationGenerator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShellType extends AbstractType
{
    private $abbreviationGenerator;

    public function __construct(ShellAbbreviationGenerator $abbreviationGenerator)
    {
        $this->abbreviationGenerator = $abbreviationGenerator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
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
                'choices' => [
                    'Les deux' => Shell::ROWING_TYPE_BOTH,
                    'Couple' => Shell::ROWING_TYPE_SCULL,
                    'Pointe' => Shell::ROWING_TYPE_SWEEP,
                ],
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
            ->add('personalBoat', CheckboxType::class, [
                'label' => 'Bâteau perso',
                'label_attr' => ['class' => 'switch-custom'],
                'required' => false,
            ])
            ->add('rowerCategory', ChoiceType::class, [
                'label' => 'Catégorie rameur',
                'choices' => [
                    'A' => Member::ROWER_CATEGORY_A,
                    'B' => Member::ROWER_CATEGORY_B,
                    'C' => Member::ROWER_CATEGORY_C,
                ],
                'expanded' => true,
                'label_attr' => ['class' => 'radio-custom'],
            ])
            ->add('productionYear', NumberType::class, [
                'label' => 'Année de fabrication',
                'html5' => true,
                'required' => false,
            ])
            ->add('weightCategory', ChoiceType::class, [
                'label' => 'Catégorie porteur',
                'choices' => [
                    '50-60' => 50,
                    '60-70' => 60,
                    '70-80' => 70,
                    '80-90' => 80,
                    '90+' => 90,
                ],
                'required' => false,
            ])
            ->add('newPrice', MoneyType::class, [
                'label' => 'Prix neuf',
                'required' => false,
            ])
            ->add('mileage', NumberType::class, [
                'label' => 'Distance parcourue',
                'html5' => true,
                'required' => false,
            ])
            ->add('riggerMaterial', ChoiceType::class, [
                'label' => 'Matériaux portants',
                'choices' => [
                    'Aluminium' => 'aluminum',
                    'Carbone' => 'carbon',
                ],
                'placeholder' => 'N/A',
                'required' => false,
                'expanded' => true,
                'label_attr' => ['class' => 'radio-custom'],
            ])
            ->add('riggerPosition', ChoiceType::class, [
                'label' => 'Position portants',
                'choices' => [
                    'Avant' => 'front',
                    'Arrière' => 'back',
                ],
                'placeholder' => 'N/A',
                'required' => false,
                'expanded' => true,
                'label_attr' => ['class' => 'radio-custom'],
            ])
            ->add('available', CheckboxType::class, [
                'label' => 'Disponible',
                'label_attr' => ['class' => 'switch-custom'],
                'required' => false,
            ])
        ;

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /* @var Shell $data */
            $data = $event->getData();

            $data->setAbbreviation($this->abbreviationGenerator->generateAbbreviation($data));
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Shell::class,
        ]);
    }
}
