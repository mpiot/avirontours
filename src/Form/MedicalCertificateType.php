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

use App\Entity\MedicalCertificate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Vich\UploaderBundle\Form\Type\VichFileType;

class MedicalCertificateType extends AbstractType
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de certificat',
                'choices' => [
                    'Attestation' => MedicalCertificate::TYPE_ATTESTATION,
                    'Certificat' => MedicalCertificate::TYPE_CERTIFICATE,
                ],
                'expanded' => true,
                'label_attr' => ['class' => 'radio-custom radio-inline'],
            ])
            ->add('level', ChoiceType::class, [
                'label' => 'Niveau',
                'choices' => [
                    'Pratique' => MedicalCertificate::LEVEL_PRACTICE,
                    'Compétition' => MedicalCertificate::LEVEL_COMPETITION,
                    'Surclassement' => MedicalCertificate::LEVEL_UPGRADE,
                ],
                'expanded' => true,
                'label_attr' => ['class' => 'radio-custom radio-inline'],
            ])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
            ])
            ->add('file', VichFileType::class, [
                'label' => 'Fichier',
                'required' => false,
                'download_uri' => function (MedicalCertificate $medicalCertificate) {
                    if (null === $medicalCertificate->getFileName()) {
                        return null;
                    }

                    return $this->router->generate('medical_certificate_download', ['id' => $medicalCertificate->getId()]);
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MedicalCertificate::class,
        ]);
    }
}
