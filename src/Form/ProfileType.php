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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->remove('subscriptionDate')
            ->remove('licenseNumber')
            ->remove('roles')
        ;

        $builder->get('gender')->setDisabled(true);
        $builder->get('firstName')->setDisabled(true);
        $builder->get('lastName')->setDisabled(true);
        $builder->get('nationality')->setDisabled(true);
        $builder->get('birthday')->setDisabled(true);
    }

    public function getParent(): string
    {
        return UserType::class;
    }
}
