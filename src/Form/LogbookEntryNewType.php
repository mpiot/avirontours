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

use App\Entity\Member;
use App\Entity\Shell;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogbookEntryNewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('shell', EntityType::class, [
                'label' => 'Bâteau',
                'class' => Shell::class,
                'choice_label' => 'fullName',
                'choice_attr' => function (Shell $shell) {
                    if ($shell->getRowerCategory() <= 2) {
                        return ['data-badge' => 'competition'];
                    }

                    if (true === $shell->getPersonalBoat()) {
                        return ['data-badge' => 'personnal'];
                    }

                    return [];
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('shell')
                        ->leftJoin('shell.logbookEntries', 'logbook_entries', 'WITH', 'logbook_entries.endAt is NULL')
                        ->where('logbook_entries is NULL')
                        ->andWhere('shell.available = true')
                        ->orderBy('shell.name', 'ASC');
                },
            ])
            ->add('crewMembers', EntityType::class, [
                'label' => 'Membres d\'équipage',
                'class' => Member::class,
                'query_builder' => function (EntityRepository $er) {
                    $subQuery = $er->createQueryBuilder('m')
                        ->select(['m.id'])
                        ->innerJoin('m.logbookEntries', 'logbook_entries', 'WITH', 'logbook_entries.endAt is NULL')
                        ->getQuery()
                        ->getArrayResult();

                    $queryBuilder = $er->createQueryBuilder('app_member');
                    $queryBuilder
                        ->select(['app_member'])
                        ->andWhere('app_member.licensedToRow = true')
                        ->andWhere('app_member.licenseEndAt >= CURRENT_DATE()')
                        ->orderBy('app_member.firstName', 'ASC')
                        ->addOrderBy('app_member.lastName', 'ASC');

                    if (!empty($subQuery)) {
                        $queryBuilder
                            ->andWhere($queryBuilder->expr()->notIn('app_member.id', ':subQuery'))
                            ->setParameter('subQuery', $subQuery);
                    }

                    return $queryBuilder;
                },
                'choice_label' => 'fullName',
                'multiple' => true,
            ])
            ->remove('endAt')
            ->remove('coveredDistance')
            ->remove('shellDamages')
        ;
    }

    public function getParent()
    {
        return LogbookEntryType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => ['Default'],
        ]);
    }
}
