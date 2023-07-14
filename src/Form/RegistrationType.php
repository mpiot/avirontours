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

use App\Entity\User;
use App\Form\Type\RegistrationLicenseType;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->remove('subscriptionDate')
            ->remove('licenseNumber')
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passes doivent être identiques.',
                'first_options' => [
                    'label' => 'Mot de passe',
                    'hash_property_path' => 'password',
                    'constraints' => [
                        new NotBlank(),
                        new Length(['min' => 6, 'max' => 4096]),
                        new NotCompromisedPassword(),
                    ],
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Répéter le mot de passe',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'mapped' => false,
            ])
            ->add('licenses', CollectionType::class, [
                'label' => false,
                'entry_type' => RegistrationLicenseType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'by_reference' => false,
            ])
            ->add('agreeSwim', CheckboxType::class, [
                'label' => 'J\'atteste savoir nager 25m avec un départ plongé',
                'label_attr' => ['class' => 'checkbox-custom'],
                'required' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez savoir nager 25m avec un départ plongé pour vous inscrire.',
                    ]),
                ],
                'mapped' => false,
            ])
            ->add('recaptcha', Recaptcha3Type::class, [
                'action_name' => 'register',
                'constraints' => [
                    new Recaptcha3(),
                ],
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['Default', 'new'],
        ]);
    }

    public function getParent(): string
    {
        return UserType::class;
    }
}
