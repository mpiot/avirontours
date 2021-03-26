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

namespace App\EventSubscriber;

use App\Entity\License;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Workflow\Event\Event;

class InvalidMedicalCertificateNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function onMedicalCertificateRejected(Event $event): void
    {
        /** @var License $license */
        $license = $event->getSubject();
        $user = $license->getUser();

        // Send email
        $email = new Email();
        $email->to($user->getEmail())
            ->text('')
            ->setHeaders(
                $email->getHeaders()
                    ->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply')
                    ->addTextHeader('X-MJ-TemplateID', '1699200')
                    ->addTextHeader('X-MJ-TemplateLanguage', '1')
                    ->addTextHeader('X-MJ-Vars', json_encode([
                        'fullName' => $user->getFullName(),
                    ]))
            )
        ;
        $this->mailer->send($email);
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.license.entered.medical_certificate_rejected' => 'onMedicalCertificateRejected',
        ];
    }
}
