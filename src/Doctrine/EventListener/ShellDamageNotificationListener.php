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

namespace App\Doctrine\EventListener;

use App\Entity\ShellDamage;
use App\Entity\ShellDamageCategory;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Routing\RouterInterface;

class ShellDamageNotificationListener
{
    private $notifier;
    private $router;

    public function __construct(NotifierInterface $notifier, RouterInterface $router)
    {
        $this->notifier = $notifier;
        $this->router = $router;
    }

    public function postPersist(ShellDamage $shellDamage, LifecycleEventArgs $args)
    {
        $importance = ShellDamageCategory::PRIORITY_HIGH === $shellDamage->getCategory()->getPriority() ? Notification::IMPORTANCE_URGENT : Notification::IMPORTANCE_MEDIUM;
        $notification = (new Notification('Nouvelle avarie'))
            ->content(sprintf('Nouvelle avarie sur le bateau: %s.', $shellDamage->getShell()->getName()))
            ->importance($importance);

        $this->notifier->send($notification, new Recipient('notifications@avirontours.fr'));
    }
}
