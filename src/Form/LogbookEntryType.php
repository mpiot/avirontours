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

use App\Entity\LogbookEntry;
use App\Entity\SeasonCategory;
use App\Entity\Shell;
use App\Entity\ShellDamage;
use App\Entity\ShellDamageCategory;
use App\Entity\User;
use App\Form\Type\NonUserCrewMemberType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogbookEntryType extends AbstractType
{
    public function __construct(private Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shell', EntityType::class, [
                'label' => 'Bâteau',
                'class' => Shell::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('shell')
                        ->select('shell')
                        ->leftJoin('shell.logbookEntries', 'logbook_entries', 'WITH', 'logbook_entries.endAt is NULL')->addSelect('logbook_entries')
                        ->leftJoin('shell.shellDamages', 'shell_damages', 'WITH', 'shell_damages.repairEndAt is NULL')->addSelect('shell_damages')
                        ->leftJoin('shell_damages.category', 'category')->addSelect('category')
                        ->orderBy('COLLATE(shell.name, fr_natural)', 'ASC')
                    ;
                },
                'choice_label' => function (Shell $shell) {
                    return $shell->getFullName().$this->shellSuffixes($shell);
                },
                'options_as_html' => true,
                'placeholder' => '--- Sélectionner un bâteau ---',
                'autocomplete' => true,
            ])
            ->add('crewMembers', EntityType::class, [
                'label' => 'Membres d\'équipage',
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('app_user')
                        ->leftJoin('app_user.logbookEntries', 'logbook_entries', 'WITH', 'logbook_entries.endAt is NULL')->addSelect('logbook_entries')
                        ->orderBy('app_user.firstName', 'ASC')
                        ->addOrderBy('app_user.lastName', 'ASC')
                    ;

                    if (!$this->security->isGranted('ROLE_LOGBOOK_ADMIN')) {
                        $qb
                            ->leftJoin('app_user.licenses', 'licenses')
                            ->leftJoin('licenses.seasonCategory', 'seasonCategory')
                            ->leftJoin('seasonCategory.season', 'season')
                            ->andWhere('seasonCategory.licenseType = :licenseType')
                            ->andWhere('season.active = true')
                            ->andWhere('JSON_GET_FIELD_AS_TEXT(licenses.marking, \'validated\') = \'1\' OR (JSON_GET_FIELD_AS_TEXT(licenses.marking, \'medical_certificate_validated\') = \'1\' AND JSON_GET_FIELD_AS_TEXT(licenses.marking, \'payment_validated\') = \'1\')')
                            ->setParameter('licenseType', SeasonCategory::LICENSE_TYPE_ANNUAL)
                        ;
                    }

                    return $qb;
                },
                'choice_label' => function (User $user) {
                    return $user->getFullName().$this->crewSuffixes($user);
                },
                'options_as_html' => true,
                'multiple' => true,
                'help' => '<div class="text-info"><span class="fa fa-info-circle"> Si un membre n\'apparaît pas dans la liste, demander à un administrateur de créer votre sortie.</span></div>',
                'help_html' => true,
                'autocomplete' => true,
            ])
            ->add('startAt', TimeType::class, [
                'label' => 'Heure de départ',
                'widget' => 'single_text',
            ])
            ->add('endAt', TimeType::class, [
                'label' => 'Heure de fin',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('coveredDistance', NumberType::class, [
                'label' => 'Distance parcourue',
                'html5' => true,
                'scale' => 1,
                'attr' => [
                    'step' => 0.1,
                ],
                'required' => false,
            ])
            ->add('shellDamages', CollectionType::class, [
                'label' => 'Avaries',
                'entry_type' => LogbookEntryShellDamageType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'attr' => [
                    'data-controller' => 'collection-type',
                    'data-collection-type-button-text-value' => 'Ajouter une avarie',
                    'data-collection-type-label-value' => 'Avarie n°',
                ],
            ])
        ;

        if ($this->security->isGranted('ROLE_LOGBOOK_ADMIN')) {
            $builder->get('crewMembers')->setRequired(false);
            $builder->add('nonUserCrewMembers', NonUserCrewMemberType::class, [
                'label' => 'Membres d\'équipage (sans utilisateur)',
                'help' => 'John Doe, Foo Bar',
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LogbookEntry::class,
            'validation_groups' => ['edit'],
        ]);
    }

    private function shellSuffixes(Shell $shell): string
    {
        $suffix = '';

        if (true === $shell->getPersonalBoat()) {
            $suffix .= '<span class="badge bg-info ms-2">Personnel</span>';
        }

        if (null !== $shell->getWeightCategory()) {
            $suffix .= '<span class="badge bg-info ms-2">'.$shell->getTextWeightCategory().'</span>';
        }

        if (false === $shell->getLogbookEntries()->isEmpty()) {
            $suffix .= '<span class="badge bg-danger ms-2"><span class="fas fa-sign-out-alt"></span></span>';
        }

        if (false === $shell->getShellDamages()->filter(fn (ShellDamage $damage) => ShellDamageCategory::PRIORITY_HIGH === $damage->getCategory()->getPriority())->isEmpty()) {
            $suffix .= '<span class="badge bg-danger ms-2"><span class="fas fa-tools"></span></span>';
        }

        return $suffix;
    }

    private function crewSuffixes(User $user): string
    {
        $suffix = '';

        if (false === $user->getLogbookEntries()->isEmpty()) {
            $suffix .= '<span class="badge bg-danger ms-2"><span class="fas fa-sign-out-alt"></span></span>';
        }

        if (false === $user->getLicenses()->isEmpty() && null !== $user->getLicenses()->last()->getLogbookEntryLimit()) {
            $suffix .= '<span class="badge bg-info ms-2">Découverte</span>';
        }

        return $suffix;
    }
}
