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

use App\Entity\Season;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeasonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', NumberType::class, [
                'label' => 'Nom',
                'help' => 'Année de la saison',
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Saison active',
                'label_attr' => ['class' => 'switch-custom'],
                'required' => false,
            ])
            ->add('subscriptionEnabled', CheckboxType::class, [
                'label' => 'Inscriptions active',
                'label_attr' => ['class' => 'switch-custom'],
                'required' => false,
            ])
            ->add('seasonCategories', CollectionType::class, [
                'label' => 'Catégories',
                'entry_type' => SeasonCategoryType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'by_reference' => false,
                'error_bubbling' => false,
                'attr' => [
                    'data-controller' => 'collection-type',
                    'data-collection-type-button-text-value' => 'Ajouter une catégorie',
                    'data-collection-type-number-entries-at-init-value' => 1,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Season::class,
        ]);
    }
}
