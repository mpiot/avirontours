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
use App\Entity\Member;
use App\Entity\Shell;
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
                    return $er->createQueryBuilder('shell')
                        ->where('shell.available = true')
                        ->orderBy('shell.name', 'ASC');
                },
                'placeholder' => '--- Sélectionner un bâteau ---',
            ])
            ->add('crewMembers', EntityType::class, [
                'label' => 'Membres d\'équipage',
                'class' => Member::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('app_member')
                        ->where('app_member.licensedToRow = true')
                        ->andWhere('app_member.licenseEndAt >= CURRENT_DATE()')
                        ->orderBy('app_member.firstName', 'ASC')
                        ->addOrderBy('app_member.lastName', 'ASC');
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
