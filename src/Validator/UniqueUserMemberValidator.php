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

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueUserMemberValidator extends ConstraintValidator
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint \App\Validator\IsCrewCategoryValid */

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof User) {
            throw new \Exception('@UserMemberUnique must be put on User::class.');
        }

        if (null === $value->getMember()) {
            return;
        }

        $user = $this->userRepository->findUserWithMember($value->getMember());

        if (null === $user) {
            return;
        }

        if ($value->getId() === $user->getId()) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value->getMember()->getFullName())
            ->atPath('member')
            ->addViolation();
    }
}
