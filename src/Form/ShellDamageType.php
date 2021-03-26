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

use App\Entity\Shell;
use App\Entity\ShellDamage;
use App\Entity\ShellDamageCategory;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShellDamageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shell', EntityType::class, [
                'label' => 'Bâteau',
                'class' => Shell::class,
                'choice_label' => 'fullName',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('shell')
                    ->orderBy('COLLATE(shell.name, fr_natural)', 'ASC'),
                'placeholder' => '--- Sélectionner un bâteau ---',
            ])
            ->add('category', EntityType::class, [
                'label' => 'Catégorie',
                'class' => ShellDamageCategory::class,
                'choice_label' => 'name',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('category')
                    ->orderBy('category.priority', 'DESC')
                    ->orderBy('category.name', 'ASC'),
                'group_by' => function (ShellDamageCategory $choice, $key, $value) {
                    if (ShellDamageCategory::PRIORITY_HIGH === $choice->getPriority()) {
                        return 'Importante';
                    }

                    return 'Intermédiaire';
                },
                'placeholder' => '--- Sélectionner une catégorie ---',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Note',
                'required' => false,
            ])
            ->add('repairStartAt', DateType::class, [
                'label' => 'Réparation commencé le',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('repairEndAt', DateType::class, [
                'label' => 'Réparé  le',
                'widget' => 'single_text',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShellDamage::class,
        ]);
    }
}
