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

use App\Entity\SeasonCategory;
use App\Entity\Shell;
use App\Entity\ShellDamageCategory;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class LogbookEntryNewType extends AbstractType
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

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
                        ->where('logbook_entries is NULL')
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
                    $unavailableUsers = $er->createQueryBuilder('u')
                        ->select(['u.id'])
                        ->innerJoin('u.logbookEntries', 'logbook_entries', 'WITH', 'logbook_entries.endAt is NULL')
                        ->getQuery()
                        ->getArrayResult();

                    $queryBuilder = $er->createQueryBuilder('app_user')
                        ->leftJoin('app_user.licenses', 'licenses')
                        ->leftJoin('licenses.seasonCategory', 'seasonCategory')
                        ->leftJoin('seasonCategory.season', 'season')
                        ->andWhere('seasonCategory.licenseType = :licenseType')
                        ->andWhere('season.active = true')
                        ->orderBy('app_user.firstName', 'ASC')
                        ->addOrderBy('app_user.lastName', 'ASC')
                        ->setParameter('licenseType', SeasonCategory::LICENSE_TYPE_ANNUAL);

                    if (!empty($unavailableUsers)) {
                        $queryBuilder
                            ->andWhere($queryBuilder->expr()->notIn('app_user.id', ':unavailableUsers'))
                            ->setParameter('unavailableUsers', $unavailableUsers);
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

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            if ($this->security->isGranted('ROLE_USER')) {
                $data = $event->getData();
                $data->addCrewMember($this->security->getUser());
                $event->setData($data);
            }
        });
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
