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

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('legalRepresentative', TextType::class, [
                'label' => 'Représentant légal',
                'help' => 'Uniquement pour mineur, majeur sous tutelle,...',
                'required' => false,
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('address', AddressType::class, [
                'inherit_data' => true,
            ])
            ->add('clubEmailAllowed', CheckboxType::class, [
                'label' => 'Recevoir les emails du club',
                'label_attr' => ['class' => 'checkbox-custom'],
                'required' => false,
            ])
            ->add('partnersEmailAllowed', CheckboxType::class, [
                'label' => 'Recevoir les emails à propos lié aux partenariats du club',
                'help' => 'Recevoir de la part du club des emails de partenaires.',
                'label_attr' => ['class' => 'checkbox-custom'],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
