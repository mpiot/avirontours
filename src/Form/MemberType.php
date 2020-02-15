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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MemberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('civility', ChoiceType::class, [
                'label' => 'Civilité',
                'choices' => [
                    'Madame' => 'madame',
                    'Monsieur' => 'monsieur',
                ],
                'expanded' => true,
                'label_attr' => ['class' => 'radio-custom radio-inline'],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('licenseNumber', TextType::class, [
                'label' => 'Numéro de license',
                'required' => false,
            ])
            ->add('licenseEndAt', DateType::class, [
                'label' => 'Date de fin de validité',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('licenseType', ChoiceType::class, [
                'label' => 'Type de license',
                'choices' => [
                    'Annuelle' => Member::LICENSE_TYPE_ANNUAL,
                    'Indoor' => Member::LICENSE_TYPE_INDOOR,
                ],
            ])
            ->add('rowerCategory', ChoiceType::class, [
                'label' => 'Catégorie rameur',
                'choices' => [
                    'A' => Member::ROWER_CATEGORY_A,
                    'B' => Member::ROWER_CATEGORY_B,
                    'C' => Member::ROWER_CATEGORY_C,
                ],
            ])
            ->add('address', AddressType::class, [
                'label' => 'Adresse',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('birthday', BirthdayType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
            ])
            ->add('legalRepresentative', TextType::class, [
                'label' => 'Représentant légal',
                'required' => false,
            ])
            ->add('subscriptionDate', DateType::class, [
                'label' => 'Date d\'inscription',
                'widget' => 'single_text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Member::class,
        ]);
    }
}
