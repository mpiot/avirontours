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

use App\Form\Model\Registration;
use App\Form\Type\RegistrationLicenseType;
use App\Form\Type\RegistrationUserType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', RegistrationUserType::class, [
                'label' => false,
            ])
            ->add('license', RegistrationLicenseType::class, [
                'label' => false,
            ])
            ->add('agreeSwim', CheckboxType::class, [
                'label' => 'J\'atteste savoir nager 25m avec un départ plongé.',
                'label_attr' => ['class' => 'checkbox-custom'],
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez savoir nager 25m avec un départ plongé pour vous inscrire.',
                    ]),
                ],
                'mapped' => false,
            ])
            ->add('agreeRulesAndRegulations', CheckboxType::class, [
                'label' => 'J\'atteste avoir lu le règlement intérieur et je l\'accepte dans son intégralité.',
                'label_attr' => ['class' => 'checkbox-custom'],
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez attester avoir avoir lu le règlement intérieur et l\'accepter dans son intégralité pour vous inscrire.',
                    ]),
                ],
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Registration::class,
            'validation_groups' => ['Default', 'registration', 'new'],
        ]);
    }
}
