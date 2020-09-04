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

namespace App\Mailer;

use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TwoFactorAuthenticationMailer implements AuthCodeMailerInterface
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        // Send email
        $email = new Email();
        $email->to($user->getEmail())
            ->text('')
            ->setHeaders(
                $email->getHeaders()
                    ->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply')
                    ->addTextHeader('X-MJ-TemplateID', '1669931')
                    ->addTextHeader('X-MJ-TemplateLanguage', '1')
                    ->addTextHeader('X-MJ-Vars', json_encode([
                        'fullName' => $user->getFullName(),
                        'authCode' => $user->getEmailAuthCode(),
                    ]))
            )
        ;
        $this->mailer->send($email);
    }
}
