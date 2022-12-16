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

namespace App\Validator;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CrewAvailableValidator extends ConstraintValidator
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CrewAvailable) {
            throw new UnexpectedTypeException($constraint, CrewAvailable::class);
        }

        if (!$value instanceof Collection) {
            throw new \Exception('The CrewAvailableValidator must be used on a User ArrayCollection.');
        }

        if ($value->isEmpty()) {
            return;
        }

        if (!$value->first() instanceof User) {
            throw new \Exception('The CrewAvailableValidator must be used on a User ArrayCollection');
        }

        $unavailableUsers = $this->userRepository->findOnWaterUsers($value->toArray());
        if (!empty($unavailableUsers)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', implode(', ', $unavailableUsers))
                ->addViolation()
            ;
        }
    }
}
