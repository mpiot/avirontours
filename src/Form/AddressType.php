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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('laneNumber', NumberType::class, [
                'label' => 'Numéro',
            ])
            ->add('laneType', ChoiceType::class, [
                'label' => 'Type de voie',
                'choices' => $this->getLaneTypes(),
            ])
            ->add('laneName', TextType::class, [
                'label' => 'Nom de voie',
            ])
            ->add('postalCode', NumberType::class, [
                'label' => 'Code postal',
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'Numéro de téléphone',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'inherit_data' => true,
        ]);
    }

    private function getLaneTypes(): array
    {
        $types = [
            'Faubourg',
            'Grande Rue',
            'Hameau',
            'Impasse',
            'Jardin',
            'Lotissement',
            'Mail',
            'Montee',
            'Parc',
            'Passage',
            'Place',
            'Plage',
            'Promenade',
            'Quai',
            'Quartier',
            'Route',
            'Rue',
            'Ruelle',
            'Sente',
            'Sentier',
            'Square',
            'Traverse',
            'Villa',
            'Village',
            'Voie',
        ];

        return array_combine($types, $types);
    }
}
