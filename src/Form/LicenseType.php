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

use App\Entity\License;
use App\Entity\Season;
use App\Entity\SeasonCategory;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LicenseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'label' => 'Utilisateur',
                'class' => User::class,
                'query_builder' => fn (EntityRepository $repository) => $repository->createQueryBuilder('app_user')
                    ->orderBy('app_user.firstName', 'ASC')
                    ->addOrderBy('app_user.lastName', 'ASC'),
                'choice_label' => 'fullName',
                'placeholder' => '--- Choisissez un utilisateur ---',
            ])
            ->add('seasonCategory', EntityType::class, [
                'label' => 'Catégorie',
                'class' => SeasonCategory::class,
                'query_builder' => function (EntityRepository $repository) use ($options) {
                    $qb = $repository->createQueryBuilder('season_category')
                        ->innerJoin('season_category.season', 'season')
                        ->orderBy('season.name', 'DESC')
                        ->addOrderBy('season_category.name', 'ASC');

                    if (null !== $season = $options['season']) {
                        $qb
                            ->where('season = :season')
                            ->setParameter('season', $season);
                    }

                    return $qb;
                },
                'choice_label' => fn (SeasonCategory $seasonCategory) => $seasonCategory->getSeason()->getName().' - '.$seasonCategory->getName(),
                'placeholder' => '--- Choisissez une catégorie ---',
            ])
            ->add('logbookEntryLimit', IntegerType::class, [
                'label' => 'Limite cahier de sortie',
                'required' => false,
            ])
            ->add('medicalCertificate', MedicalCertificateType::class, [
                'label' => false,
            ])
            ->add('federationEmailAllowed', CheckboxType::class, [
                'label' => 'Recevoir les emails de la Fédération Française d\'Aviron',
                'label_attr' => ['class' => 'checkbox-custom'],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => License::class,
            'season' => null,
        ]);

        $resolver->setAllowedTypes('season', [Season::class, 'null']);
    }
}
