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
use Symfony\Component\DomCrawler\Crawler;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

abstract class AppWebTestCase extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        error_reporting(\E_ALL);
    }

    protected function createAndLogin(AbstractBrowser $client, string $role): User|Proxy
    {
        $user = UserFactory::new(['roles' => [$role]])
            ->major()
            ->create()
        ;

        $client->loginUser($user);

        return $user;
    }

    protected function filterFormErrors(Crawler $crawler, string $selector, string $type = 'input', string $ancestorSelector = 'div', string $ancestorClass = 'mb-3'): Crawler
    {
        return $crawler->filterXPath(\sprintf(
            '//%s[@id="%s"]/ancestor::%s[@class="%s"]/div[@class="invalid-feedback d-block"]',
            $type,
            $selector,
            $ancestorSelector,
            $ancestorClass
        ));
    }

    protected function getIpV4(): string
    {
        return long2ip(random_int(0, 4294967295));
    }

    protected static function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }
}
