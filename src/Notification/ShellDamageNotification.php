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

namespace App\Notification;

use App\Entity\ShellDamage;
use App\Entity\ShellDamageCategory;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\Recipient;

class ShellDamageNotification extends Notification implements EmailNotificationInterface
{
    private $shellDamage;

    public function __construct(ShellDamage $shellDamage)
    {
        $this->shellDamage = $shellDamage;
        $this->importance(ShellDamageCategory::PRIORITY_HIGH === $shellDamage->getCategory()->getPriority() ? Notification::IMPORTANCE_URGENT : Notification::IMPORTANCE_MEDIUM);

        parent::__construct('Nouvelle avarie');
    }

    public function asEmailMessage(Recipient $recipient, string $transport = null): ?EmailMessage
    {
        $message = EmailMessage::fromNotification($this, $recipient, $transport);
        $message->getMessage()
            ->htmlTemplate('emails/shell_damage_notification.html.twig')
            ->context(['shellDamage' => $this->shellDamage]);

        return $message;
    }
}
