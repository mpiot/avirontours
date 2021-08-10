<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;

class SecurityControllerTest extends AppWebTestCase
{
    public function testLogin(): void
    {
        UserFactory::createOne(['firstName' => 'firstname', 'lastName' => 'lastname']);

        static::ensureKernelShutdown();
        $client = static::createClient([], ['REMOTE_ADDR' => UserFactory::faker()->ipv4()]);
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Se connecter', [
            'username' => 'firstname.lastname',
            'password' => UserFactory::PASSWORD,
        ]);

        $this->assertResponseRedirects('/');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testLoginWithCaps(): void
    {
        UserFactory::createOne(['firstName' => 'firstname', 'lastName' => 'lastname']);

        static::ensureKernelShutdown();
        $client = static::createClient([], ['REMOTE_ADDR' => UserFactory::faker()->ipv4()]);
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Se connecter', [
            'username' => 'FIRSTNAME.LASTNAME',
            'password' => UserFactory::PASSWORD,
        ]);

        $this->assertResponseRedirects('/');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testLoginWithBadUsername(): void
    {
        UserFactory::createOne(['firstName' => 'firstname', 'lastName' => 'lastname']);

        static::ensureKernelShutdown();
        $client = static::createClient([], ['REMOTE_ADDR' => UserFactory::faker()->ipv4()]);
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Se connecter', [
            'username' => 'IDoNotExist',
            'password' => UserFactory::PASSWORD,
        ]);
        $this->assertResponseRedirects('/login');
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('Identifiants invalides.', $crawler->filter('div.alert-danger')->text());
    }

    public function testLoginWithBadPassword(): void
    {
        UserFactory::createOne(['firstName' => 'firstname', 'lastName' => 'lastname']);

        static::ensureKernelShutdown();
        $client = static::createClient([], ['REMOTE_ADDR' => UserFactory::faker()->ipv4()]);
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Se connecter', [
            'username' => 'firstname.lastname',
            'password' => 'IDoNotExist',
        ]);

        $this->assertResponseRedirects('/login');
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('Identifiants invalides.', $crawler->filter('div.alert-danger')->text());
    }

    public function testLoginWithBadUsernameAndIPTooManyTimes(): void
    {
        UserFactory::createOne(['firstName' => 'firstname', 'lastName' => 'lastname']);

        static::ensureKernelShutdown();
        $client = static::createClient([], ['REMOTE_ADDR' => UserFactory::faker()->ipv4()]);
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        for ($i = 0; $i < 5; ++$i) {
            $client->submitForm('Se connecter', [
                'username' => 'firstname.lastname',
                'password' => 'IDoNotExist',
            ]);

            $this->assertResponseRedirects('/login');
            $crawler = $client->followRedirect();
            $this->assertStringContainsString('Identifiants invalides.', $crawler->filter('div.alert-danger')->text());
        }

        $client->submitForm('Se connecter', [
            'username' => 'firstname.lastname',
            'password' => UserFactory::PASSWORD,
        ]);

        $this->assertResponseRedirects('/login');
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('Plusieurs tentatives de connexion ont échoué, veuillez réessayer dans 1 minute.', $crawler->filter('div.alert-danger')->text());
    }

    public function testLoginWithIPTooManyTimes(): void
    {
        UserFactory::createOne(['firstName' => 'firstname', 'lastName' => 'lastname']);

        static::ensureKernelShutdown();
        $client = static::createClient([], ['REMOTE_ADDR' => UserFactory::faker()->ipv4()]);
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        for ($i = 0; $i < 25; ++$i) {
            $client->submitForm('Se connecter', [
                'username' => UserFactory::faker()->userName(),
                'password' => 'IDoNotExist',
            ]);

            $this->assertResponseRedirects('/login');
            $client->followRedirect();
        }

        $client->submitForm('Se connecter', [
            'username' => 'firstname.lastname',
            'password' => UserFactory::PASSWORD,
        ]);

        $this->assertResponseRedirects('/login');
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('Plusieurs tentatives de connexion ont échoué, veuillez réessayer dans 1 minute.', $crawler->filter('div.alert-danger')->text());
    }
}
