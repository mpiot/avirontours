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

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LicenseEditType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->remove('user');
        $builder->add('medicalCertificate', MedicalCertificateEditType::class, [
            'label' => false,
        ]);

        if (false === $this->security->isGranted('ROLE_SEASON_PAYMENTS_ADMIN')) {
            $builder->remove('payments');
        }

        if (false === $this->security->isGranted('ROLE_SEASON_MEDICAL_CERTIFICATE_ADMIN')) {
            $builder->remove('medicalCertificate');
        }
    }

    public function getParent(): string
    {
        return LicenseType::class;
    }
}
