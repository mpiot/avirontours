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

namespace App\Tests;

use App\Entity\User;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

abstract class AppWebTestCase extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    /**
     * @return User|Proxy
     */
    protected function logIn(AbstractBrowser $client, string $role): Proxy
    {
        /** @var User|Proxy $user */
        $user = UserFactory::createOne(['roles' => [$role]]);

        $client->loginUser($user->object());

        return $user;
    }

    protected static function getEntityManager(): EntityManagerInterface
    {
        return self::$container->get('doctrine')->getManager();
    }
}
