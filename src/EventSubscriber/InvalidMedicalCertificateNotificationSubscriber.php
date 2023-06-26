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
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Workflow\Event\Event;

class InvalidMedicalCertificateNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    public function onMedicalCertificateRejected(Event $event): void
    {
        /** @var License $license */
        $license = $event->getSubject();
        $user = $license->getUser();

        // Send email
        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject('Certificat médical invalide')
            ->htmlTemplate('emails/invalid_certificate.html.twig')
            ->context([
                'user' => $user,
            ])
        ;
        $this->mailer->send($email);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.license.entered.medical_certificate_rejected' => 'onMedicalCertificateRejected',
        ];
    }
}
