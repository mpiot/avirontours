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

namespace App\Mailer;

use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class TwoFactorAuthenticationMailer implements AuthCodeMailerInterface
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        // Send email
        $email = (new TemplatedEmail())
            ->to($user->getEmailAuthRecipient())
            ->subject('Code d\'authentification')
            ->htmlTemplate('emails/2fa.html.twig')
            ->context([
                'user' => $user,
            ])
        ;
        $this->mailer->send($email);
    }
}
