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

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LaneTypeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => $this->getLaneTypes(),
            'placeholder' => '--- Sélectionner un type de voie ---',
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    private function getLaneTypes(): array
    {
        $types = [
            'Allee',
            'Avenue',
            'Boulevard',
            'Carrefour',
            'Chaussee',
            'Chemin',
            'Cite',
            'Clos',
            'Cours',
            'Domaine',
            'Enclos',
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
