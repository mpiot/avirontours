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

namespace App\Form\DataTransformer;

use App\Entity\Invitation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CodeToInvitationTransformer implements DataTransformerInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function transform($invitation)
    {
        if (null === $invitation) {
            return '';
        }

        return $invitation->getCode();
    }

    public function reverseTransform($invitationCode)
    {
        // no issue number? It's optional, so that's ok
        if (!$invitationCode) {
            return;
        }

        $invitation = $this->entityManager
            ->getRepository(Invitation::class)
            // query for the issue with this id
            ->findOneBy(['code' => $invitationCode]);

        if (null === $invitation) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(sprintf('An invitation with code "%s" does not exist!', $invitation));
        }

        return $invitation;
    }
}
