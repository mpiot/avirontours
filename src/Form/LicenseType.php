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

use App\Entity\License;
use App\Entity\SeasonCategory;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LicenseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('seasonCategory', EntityType::class, [
                'label' => 'Catégorie',
                'class' => SeasonCategory::class,
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('season_category')
                        ->innerJoin('season_category.season', 'season')
                        ->where('season.subscriptionEnabled = true')
                        ->orderBy('season_category.name', 'ASC');
                },
                'choice_label' => function (SeasonCategory $seasonCategory) {
                    return $seasonCategory->getSeason()->getName().' - '.$seasonCategory->getName();
                },
            ])
            ->add('medicalCertificate', MedicalCertificateType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => License::class,
        ]);
    }
}
