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

use App\Entity\Anatomy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnatomyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('height', IntegerType::class, [
                'label' => 'Taille',
                'help' => 'En cm',
                'required' => false,
            ])
            ->add('weight', NumberType::class, [
                'label' => 'Poids',
                'required' => false,
            ])
            ->add('armSpan', IntegerType::class, [
                'label' => 'Envergure',
                'help' => 'En cm',
                'required' => false,
            ])
            ->add('bustLength', IntegerType::class, [
                'label' => 'Longueur du buste',
                'help' => 'En cm',
                'required' => false,
            ])
            ->add('legLength', IntegerType::class, [
                'label' => 'Longueur des jambes',
                'help' => 'En cm',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Anatomy::class,
        ]);
    }
}
