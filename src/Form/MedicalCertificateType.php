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

use App\Entity\MedicalCertificate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class MedicalCertificateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de certificat',
                'choices' => MedicalCertificate::getAvailableTypes(),
                'expanded' => true,
                'label_attr' => ['class' => 'radio-custom radio-inline'],
            ])
            ->add('level', ChoiceType::class, [
                'label' => 'Niveau',
                'choices' => MedicalCertificate::getAvailableLevels(),
                'expanded' => true,
                'label_attr' => ['class' => 'radio-custom radio-inline'],
            ])
            ->add('date', DateType::class, [
                'label' => 'Date du document',
                'widget' => 'single_text',
            ])
            ->add('file', FileType::class, [
                'label' => 'Fichier',
                'constraints' => [
                    new NotNull(),
                    new File(
                        maxSize: '3M',
                        mimeTypes: ['application/pdf', 'application/x-pdf', 'image/*'],
                        mimeTypesMessage: 'Le fichier doit être au format PDF ou bien une image.',
                    ),
                ],
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MedicalCertificate::class,
        ]);
    }
}
