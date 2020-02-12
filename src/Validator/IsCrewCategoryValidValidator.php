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

namespace App\Validator;

use App\Entity\LogbookEntry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsCrewCategoryValidValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint \App\Validator\IsCrewCategoryValid */

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof LogbookEntry) {
            throw new \Exception('@IsCrewCategoryValid must be put on LogBookEntry::class.');
        }

        $invalidMembers = [];
        foreach ($value->getCrewMembers() as $crewMember) {
            if ($crewMember->getRowerCategory() > $value->getShell()->getRowerCategory()) {
                $invalidMembers[] = $crewMember->getFullName();
            }
        }

        if (!empty($invalidMembers)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ invalidMembers }}', implode(', ', $invalidMembers))
                ->atPath('crewMembers')
                ->addViolation();
        }
    }
}
