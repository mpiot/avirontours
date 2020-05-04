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

use App\Entity\LogbookEntry;
use App\Entity\Shell;
use App\Entity\ShellDamageCategory;
use App\Entity\User;
use App\Form\Type\ShellDamageType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogbookEntryType extends AbstractType
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
                    $unavailableShells = $er->createQueryBuilder('s')
                        ->select(['s.id'])
                        ->innerJoin('s.shellDamages', 'shell_damages', 'WITH', 'shell_damages.repairAt is NULL')
                        ->innerJoin('shell_damages.category', 'category', 'WITH', 'category.priority = :priority_high')
                        ->setParameter('priority_high', ShellDamageCategory::PRIORITY_HIGH)
                        ->getQuery()
                        ->getArrayResult();

                    $queryBuilder = $er->createQueryBuilder('shell');
                    $queryBuilder
                        ->select('shell')
                        ->leftJoin('shell.logbookEntries', 'logbook_entries', 'WITH', 'logbook_entries.endAt is NULL')
                        ->orderBy('COLLATE(shell.name, fr_natural)', 'ASC');

                    if (!empty($unavailableShells)) {
                        $queryBuilder
                            ->andWhere($queryBuilder->expr()->notIn('shell.id', ':unavailableShells'))
                            ->setParameter('unavailableShells', $unavailableShells);
                    }

                    return $queryBuilder;
                },
                'placeholder' => '--- Sélectionner un bâteau ---',
            ])
            ->add('crewMembers', EntityType::class, [
                'label' => 'Membres d\'équipage',
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('app_user')
                        ->orderBy('app_user.firstName', 'ASC')
                        ->addOrderBy('app_user.lastName', 'ASC');
                },
                'choice_label' => 'fullName',
                'multiple' => true,
            ])
            ->add('startAt', TimeType::class, [
                'label' => 'Heure de départ',
                'widget' => 'single_text',
            ])
            ->add('endAt', TimeType::class, [
                'label' => 'Heure de fin',
                'widget' => 'single_text',
            ])
            ->add('coveredDistance', NumberType::class, [
                'label' => 'Distance parcourue',
                'html5' => true,
                'scale' => 1,
                'attr' => [
                    'step' => 0.1,
                ],
            ])
            ->add('shellDamages', CollectionType::class, [
                'label' => 'Avaries',
                'entry_type' => ShellDamageType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LogbookEntry::class,
            'validation_groups' => ['Default', 'finish'],
        ]);
    }
}
