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

namespace App\Doctrine\EventListener;

use App\Entity\Invitation;
use App\Entity\User;
use Doctrine\ORM\Event\LifecycleEventArgs;

final class RegistrationListener
{
    public function postPersist(User $user, LifecycleEventArgs $args): void
    {
        $member = $user->getMember();

        if (null === $member) {
            return;
        }

        $objectManager = $args->getObjectManager();
        $invitation = $objectManager->getRepository(Invitation::class)->findOneBy(['member' => $member]);

        $objectManager->remove($invitation);
        $objectManager->flush();
    }
}
