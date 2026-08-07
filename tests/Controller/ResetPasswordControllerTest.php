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

namespace App\Tests\Controller;

use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResetPasswordControllerTest extends AppWebTestCase
{
    public function testRequestSendsAResetEmail(): void
    {
        $user = UserFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/reset-password');
        $client->submitForm('Envoyer un email de récupération', [
            'reset_password_request_form[username]' => $user->getUsername(),
        ]);

        $this->assertResponseRedirects('/reset-password/check-email');
        self::assertQueuedEmailCount(1);
        self::assertEmailAddressContains($this->getMailerMessage(), 'Reply-To', 'contact@avirontours.fr');
    }

    public function testResetChangesThePassword(): void
    {
        $user = UserFactory::createOne();
        $oldPassword = $user->getPassword();
        $token = self::getContainer()->get(ResetPasswordHelperInterface::class)->generateResetToken($user)->getToken();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', "/reset-password/reset/{$token}");
        $client->followRedirect();

        $client->submitForm('Réinitialiser le mot de passe', [
            'change_password_form[plainPassword][first]' => 'a-brand-new-password',
            'change_password_form[plainPassword][second]' => 'a-brand-new-password',
        ]);

        $this->assertResponseRedirects('/login');
        self::assertNotSame($oldPassword, $user->getPassword());
    }

    public function testResetWithAnInvalidTokenRedirectsToTheRequestPage(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/reset-password/reset/an-invalid-token');
        $client->followRedirect();

        $this->assertResponseRedirects('/reset-password');
    }
}
