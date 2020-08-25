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

use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

class RenewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('agreeSwim', CheckboxType::class, [
                'label' => 'J\'atteste savoir nager',
                'label_attr' => ['class' => 'checkbox-custom'],
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez savoir nager pour vous inscrire.',
                    ]),
                ],
            ])
            ->add('recaptcha', Recaptcha3Type::class, [
                'action_name' => 'register',
                'mapped' => false,
                'constraints' => [
                    new Recaptcha3(),
                ],
            ])
            ->remove('seasonCategory')
            ->remove('user')
            ->get('medicalCertificate')->get('file')->setRequired(true)
        ;
    }

    public function getParent()
    {
        return LicenseType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => ['Default', 'new'],
        ]);
    }
}
