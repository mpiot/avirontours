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
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\DataProvider;

class Concept2OauthControllerTest extends AppWebTestCase
{
    #[DataProvider('urlProvider')]
    public function testAccessDeniedForAnonymousUser(string $url): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertResponseRedirects('http://localhost/login');
    }

    public function testConnectCheckStoresTheTokens(): void
    {
        $user = UserFactory::new(['concept2RefreshToken' => null])->major()->create();
        $expiresAt = new \DateTimeImmutable('+14 days');
        $oauthClient = $this->createStub(OAuth2Client::class);
        $oauthClient->method('getAccessToken')->willReturn(new AccessToken([
            'access_token' => 'a-jwt',
            'refresh_token' => 'new-refresh',
            'expires' => $expiresAt->getTimestamp(),
        ]));

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $this->stubOauthClient($oauthClient);

        $client->request('GET', '/oauth/concept-logbook');

        $this->assertResponseRedirects('/sport-profile/configuration');
        $this->assertSame('new-refresh', $user->getConcept2RefreshToken());
        $this->assertSame('a-jwt', $user->getConcept2AccessToken());
        $this->assertSame($expiresAt->getTimestamp(), $user->getConcept2AccessTokenExpiresAt()->getTimestamp());
    }

    public function testConnectCheckRejectsATokenResponseWithoutExpiry(): void
    {
        $user = UserFactory::new(['concept2RefreshToken' => null])->major()->create();
        $oauthClient = $this->createStub(OAuth2Client::class);
        $oauthClient->method('getAccessToken')->willReturn(new AccessToken([
            'access_token' => 'a-jwt',
            'refresh_token' => 'new-refresh',
        ]));

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $this->stubOauthClient($oauthClient);

        $client->request('GET', '/oauth/concept-logbook');

        $this->assertResponseRedirects('/sport-profile/configuration');
        $crawler = $client->followRedirect();
        $this->assertStringContainsString(
            'Une erreur est survenue lors de la connexion de votre compte Concept2.',
            $crawler->filterXPath('//*[@id="flashes"]')->text()
        );
        $this->assertNull($user->getConcept2RefreshToken());
        $this->assertNull($user->getConcept2AccessToken());
        $this->assertNull($user->getConcept2AccessTokenExpiresAt());
    }

    public function testConnectCheckKeepsTheTokensWhenConcept2RejectsTheExchange(): void
    {
        $expiresAt = new \DateTimeImmutable('+2 hours');
        $user = UserFactory::new([
            'concept2RefreshToken' => 'existing-refresh',
            'concept2AccessToken' => 'stored-jwt',
            'concept2AccessTokenExpiresAt' => $expiresAt,
        ])->major()->create();
        $oauthClient = $this->createStub(OAuth2Client::class);
        $oauthClient->method('getAccessToken')->willThrowException(
            new IdentityProviderException('invalid_grant', 400, '')
        );

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $this->stubOauthClient($oauthClient);

        $client->request('GET', '/oauth/concept-logbook');

        $this->assertResponseRedirects('/sport-profile/configuration');
        $crawler = $client->followRedirect();
        $this->assertStringContainsString(
            'Une erreur est survenue lors de la connexion de votre compte Concept2.',
            $crawler->filterXPath('//*[@id="flashes"]')->text()
        );
        $this->assertSame('existing-refresh', $user->getConcept2RefreshToken());
        $this->assertSame('stored-jwt', $user->getConcept2AccessToken());
        $this->assertSame($expiresAt->getTimestamp(), $user->getConcept2AccessTokenExpiresAt()->getTimestamp());
    }

    public function testUnconnectClearsTheStoredTokens(): void
    {
        $user = UserFactory::new([
            'concept2RefreshToken' => 'existing-refresh',
            'concept2AccessToken' => 'stored-jwt',
            'concept2AccessTokenExpiresAt' => new \DateTimeImmutable('+2 hours'),
        ])->major()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);

        $client->request('GET', '/oauth/concept-logbook/unconnect');

        $this->assertResponseRedirects('/sport-profile/configuration');
        $this->assertNull($user->getConcept2RefreshToken());
        $this->assertNull($user->getConcept2AccessToken());
        $this->assertNull($user->getConcept2AccessTokenExpiresAt());
    }

    private function stubOauthClient(OAuth2Client $oauthClient): void
    {
        $clientRegistry = $this->createStub(ClientRegistry::class);
        $clientRegistry->method('getClient')->willReturn($oauthClient);

        self::getContainer()->set('knpu.oauth2.registry', $clientRegistry);
    }

    public static function urlProvider(): \Generator
    {
        yield ['/oauth/concept-logbook/connect'];
        yield ['/oauth/concept-logbook'];
        yield ['/oauth/concept-logbook/unconnect'];
    }
}
