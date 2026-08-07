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

use App\Entity\LegalGuardian;
use App\Entity\License;
use App\Entity\SeasonCategory;
use App\Entity\User;
use App\Enum\CertificateLevel;
use App\Enum\CertificateType;
use App\Enum\LegalGuardianRole;
use App\Factory\LicenseFactory;
use App\Factory\MedicalCertificateFactory;
use App\Factory\PostalCodeFactory;
use App\Factory\SeasonCategoryFactory;
use App\Factory\SeasonFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

class RegistrationControllerTest extends AppWebTestCase
{
    public function testRegistration(): void
    {
        [$client, $seasonCategory] = $this->openRegistration();

        $crawler = $client->submitForm('Valider mon inscription', [
            'registration[user][postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('Valider mon inscription')->form([
            'registration[user][gender]' => 'm',
            'registration[user][firstName]' => 'John',
            'registration[user][lastName]' => 'Doe',
            'registration[user][email]' => 'john.doe@avirontours.fr',
            'registration[user][phoneNumber]' => '0102030405',
            'registration[user][plainPassword][first]' => 'engage',
            'registration[user][plainPassword][second]' => 'engage',
            'registration[user][nationality]' => 'FR',
            'registration[user][birthday]' => '2010-01-01',
            'registration[user][laneNumber]' => '100',
            'registration[user][laneType]' => 'Rue',
            'registration[user][laneName]' => 'du test',
            'registration[user][city]' => 'One City',
            'registration[user][clubEmailAllowed]' => 1,
            'registration[user][firstLegalGuardian][role]' => LegalGuardianRole::Father->value,
            'registration[user][firstLegalGuardian][firstName]' => 'Gandalf',
            'registration[user][firstLegalGuardian][lastName]' => 'Le Blanc',
            'registration[user][firstLegalGuardian][email]' => 'g.le-blanc@avirontours.fr',
            'registration[user][firstLegalGuardian][phoneNumber]' => '0123456788',
            'registration[user][secondLegalGuardian][role]' => LegalGuardianRole::Mother->value,
            'registration[user][secondLegalGuardian][firstName]' => 'Galadriel',
            'registration[user][secondLegalGuardian][lastName]' => 'Artanis',
            'registration[user][secondLegalGuardian][email]' => 'g.artanis@avirontours.fr',
            'registration[user][secondLegalGuardian][phoneNumber]' => '0123456799',
            'registration[license][medicalCertificate][type]' => CertificateType::Certificate->value,
            'registration[license][medicalCertificate][level]' => CertificateLevel::Competition->value,
            'registration[license][medicalCertificate][date]' => (new \DateTime())->format('Y-m-d'),
            'registration[license][optionalInsurance]' => 1,
            'registration[license][federationEmailAllowed]' => 1,
            'registration[agreeSwim]' => 1,
            'registration[agreeRulesAndRegulations]' => 1,
            'registration[agreeMedicalCertificate]' => 1,
        ]);
        $form['registration[license][medicalCertificate][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/document.pdf');
        $client->submit($form);

        self::assertResponseRedirects('/register/confirmation/minor');

        $this->assertRegistrationEmail(renew: false, isMajor: false, legalGuardianEmails: ['g.le-blanc@avirontours.fr', 'g.artanis@avirontours.fr']);

        self::getEntityManager()->clear();

        $user = UserFactory::repository()->findOneBy(['email' => 'john.doe@avirontours.fr']);
        self::assertSame((new \DateTime())->format('Y-m-d'), $user->getSubscriptionDate()->format('Y-m-d'));
        self::assertSame('m', $user->getGender());
        self::assertSame('John', $user->getFirstName());
        self::assertSame('Doe', $user->getLastName());
        self::assertSame('john.doe', $user->getUsername());
        self::assertSame('0102030405', $user->getPhoneNumber());
        self::assertNotNull($user->getPassword());
        self::assertSame('FR', $user->getNationality());
        self::assertSame('2010-01-01', $user->getBirthday()->format('Y-m-d'));
        self::assertSame('100', $user->getLaneNumber());
        self::assertSame('Rue', $user->getLaneType());
        self::assertSame('Du Test', $user->getLaneName());
        self::assertSame('01000', $user->getPostalCode());
        self::assertSame('One City', $user->getCity());
        self::assertTrue($user->getClubEmailAllowed());
        self::assertSame(LegalGuardianRole::Father, $user->getFirstLegalGuardian()->getRole());
        self::assertSame('Gandalf', $user->getFirstLegalGuardian()->getFirstName());
        self::assertSame('Le Blanc', $user->getFirstLegalGuardian()->getLastName());
        self::assertSame('g.le-blanc@avirontours.fr', $user->getFirstLegalGuardian()->getEmail());
        self::assertSame('0123456788', $user->getFirstLegalGuardian()->getPhoneNumber());
        self::assertSame(LegalGuardianRole::Mother, $user->getSecondLegalGuardian()->getRole());
        self::assertSame('Galadriel', $user->getSecondLegalGuardian()->getFirstName());
        self::assertSame('Artanis', $user->getSecondLegalGuardian()->getLastName());
        self::assertSame('g.artanis@avirontours.fr', $user->getSecondLegalGuardian()->getEmail());
        self::assertSame('0123456799', $user->getSecondLegalGuardian()->getPhoneNumber());

        $license = $user->getLicenses()->first();
        self::assertCount(1, $user->getLicenses());
        self::assertSame($seasonCategory->getId(), $license->getSeasonCategory()->getId());
        self::assertTrue($license->getFederationEmailAllowed());
        self::assertTrue($license->getOptionalInsurance());
        self::assertSame(CertificateType::Certificate, $license->getMedicalCertificate()->getType());
        self::assertSame(CertificateLevel::Competition, $license->getMedicalCertificate()->getLevel());
        self::assertSame((new \DateTime())->format('Y-m-d'), $license->getMedicalCertificate()->getDate()->format('Y-m-d'));
        self::assertNotNull($license->getMedicalCertificate()->getUploadedFile());
        self::assertSame(
            ['wait_medical_certificate_validation' => 1, 'wait_payment_validation' => 1],
            $license->getMarking()
        );

        UserFactory::repository()->assert()->count(1);
        LicenseFactory::repository()->assert()->count(1);
        MedicalCertificateFactory::repository()->assert()->count(1);
    }

    public function testRegistrationIsRefusedWithoutAnyData(): void
    {
        [$client] = $this->openRegistration();

        $crawler = $client->submitForm('Valider mon inscription', [
            'registration[user][nationality]' => '',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertCount(0, $crawler->filterXPath('//*[@class="alert alert-danger d-block"]'));
        self::assertCount(19, $crawler->filterXPath('//*[@class="invalid-feedback d-block"]'));
        self::assertStringContainsString('Cette valeur ne doit pas être vide.', $this->filterFormErrors($crawler, 'registration_user_gender', 'div', 'fieldset')->text());
        self::assertStringContainsString('Cette valeur ne doit pas être vide.', $this->filterFormErrors($crawler, 'registration_user_firstName')->text());
        self::assertStringContainsString('Cette valeur ne doit pas être vide.', $this->filterFormErrors($crawler, 'registration_user_lastName')->text());
        self::assertStringContainsString('Cette valeur ne doit pas être vide.', $this->filterFormErrors($crawler, 'registration_user_email')->text());
        self::assertStringContainsString('Cette valeur ne doit pas être vide.', $this->filterFormErrors($crawler, 'registration_user_plainPassword_first')->text());
        self::assertStringContainsString('Cette valeur ne doit pas être vide.', $this->filterFormErrors($crawler, 'registration_user_nationality', 'select')->text());
        self::assertStringContainsString('Cette valeur ne doit pas être vide.', $this->filterFormErrors($crawler, 'registration_user_birthday')->text());
        self::assertStringContainsString('Cette valeur ne doit pas être vide.', $this->filterFormErrors($crawler, 'registration_user_laneNumber')->text());
        self::assertStringContainsString('Cette valeur ne doit pas être nulle.', $this->filterFormErrors($crawler, 'registration_user_laneType', 'select')->text());
        self::assertStringContainsString('Cette valeur ne doit pas être vide.', $this->filterFormErrors($crawler, 'registration_user_laneName')->text());
        self::assertStringContainsString('Cette valeur ne doit pas être vide.', $this->filterFormErrors($crawler, 'registration_user_postalCode')->text());
        self::assertStringContainsString('Cette valeur ne doit pas être vide.', $this->filterFormErrors($crawler, 'registration_user_city', 'select')->text());
        self::assertStringContainsString('Cette valeur ne doit pas être vide.', $this->filterFormErrors($crawler, 'registration_license_medicalCertificate_type', 'div', 'fieldset')->text());
        self::assertStringContainsString('Cette valeur ne doit pas être vide.', $this->filterFormErrors($crawler, 'registration_license_medicalCertificate_level', 'div', 'fieldset')->text());
        self::assertStringContainsString('Cette valeur ne doit pas être vide.', $this->filterFormErrors($crawler, 'registration_license_medicalCertificate_date')->text());
        self::assertStringContainsString('Cette valeur ne doit pas être nulle.', $this->filterFormErrors($crawler, 'registration_license_medicalCertificate_file')->text());
        self::assertStringContainsString('Vous devez savoir nager 25m avec un départ plongé pour vous inscrire.', $this->filterFormErrors($crawler, 'registration_agreeSwim')->text());
        self::assertStringContainsString('Vous devez attester avoir lu le règlement intérieur et l\'accepter dans son intégralité pour vous inscrire.', $this->filterFormErrors($crawler, 'registration_agreeRulesAndRegulations')->text());
        self::assertStringContainsString('Vous devez attester que le document joint est valide pour vous inscrire.', $this->filterFormErrors($crawler, 'registration_agreeMedicalCertificate')->text());

        UserFactory::repository()->assert()->count(0);
        LicenseFactory::repository()->assert()->count(0);
        MedicalCertificateFactory::repository()->assert()->count(0);
    }

    public function testRegistrationIsRefusedForAMinorWithoutALegalGuardian(): void
    {
        [$client] = $this->openRegistration();

        $crawler = $client->submitForm('Valider mon inscription', [
            'registration[user][postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('Valider mon inscription')->form([
            'registration[user][gender]' => 'm',
            'registration[user][firstName]' => 'John',
            'registration[user][lastName]' => 'Doe',
            'registration[user][email]' => 'john.doe@avirontours.fr',
            'registration[user][plainPassword][first]' => 'engage',
            'registration[user][plainPassword][second]' => 'engage',
            'registration[user][nationality]' => 'FR',
            'registration[user][birthday]' => (new \DateTime('-15 years'))->format('Y-m-d'),
            'registration[user][laneNumber]' => '100',
            'registration[user][laneType]' => 'Rue',
            'registration[user][laneName]' => 'du test',
            'registration[user][city]' => 'One City',
            'registration[license][medicalCertificate][type]' => CertificateType::Certificate->value,
            'registration[license][medicalCertificate][level]' => CertificateLevel::Competition->value,
            'registration[license][medicalCertificate][date]' => (new \DateTime())->format('Y-m-d'),
            'registration[agreeSwim]' => 1,
            'registration[agreeRulesAndRegulations]' => 1,
            'registration[agreeMedicalCertificate]' => 1,
        ]);
        $form['registration[license][medicalCertificate][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/document.pdf');
        $crawler = $client->submit($form);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertCount(1, $crawler->filterXPath('//*[@class="alert alert-danger d-block"]'));
        self::assertCount(0, $crawler->filterXPath('//*[@class="invalid-feedback d-block"]'));
        self::assertStringContainsString('Le membre est mineur, merci de renseigner un représentant légal.', $crawler->filterXPath('//*[@class="alert alert-danger d-block"]')->text());

        UserFactory::repository()->assert()->count(0);
        LicenseFactory::repository()->assert()->count(0);
        MedicalCertificateFactory::repository()->assert()->count(0);
    }

    public function testRegistrationSucceedsForAnAdultWithoutALegalGuardian(): void
    {
        [$client] = $this->openRegistration();

        $crawler = $client->submitForm('Valider mon inscription', [
            'registration[user][postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('Valider mon inscription')->form([
            'registration[user][gender]' => 'm',
            'registration[user][firstName]' => 'John',
            'registration[user][lastName]' => 'Doe',
            'registration[user][email]' => 'john.doe@avirontours.fr',
            'registration[user][plainPassword][first]' => 'engage',
            'registration[user][plainPassword][second]' => 'engage',
            'registration[user][nationality]' => 'FR',
            'registration[user][birthday]' => (new \DateTime('-20 years'))->format('Y-m-d'),
            'registration[user][laneNumber]' => '100',
            'registration[user][laneType]' => 'Rue',
            'registration[user][laneName]' => 'du test',
            'registration[user][city]' => 'One City',
            'registration[license][medicalCertificate][type]' => CertificateType::Certificate->value,
            'registration[license][medicalCertificate][level]' => CertificateLevel::Competition->value,
            'registration[license][medicalCertificate][date]' => (new \DateTime())->format('Y-m-d'),
            'registration[agreeSwim]' => 1,
            'registration[agreeRulesAndRegulations]' => 1,
            'registration[agreeMedicalCertificate]' => 1,
        ]);
        $form['registration[license][medicalCertificate][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/document.pdf');
        $client->submit($form);

        self::assertResponseRedirects('/register/confirmation/major');

        $this->assertRegistrationEmail(renew: false, isMajor: true);

        UserFactory::repository()->assert()->count(1);
        LicenseFactory::repository()->assert()->count(1);
        MedicalCertificateFactory::repository()->assert()->count(1);
    }

    #[DataProvider('provideConfirmationDocuments')]
    public function testRegisterConfirmationListsTheDocumentsToReturn(string $majority, string $expectedDocuments, string $unexpectedDocuments): void
    {
        $client = static::createClient();
        $client->request('GET', "/register/confirmation/{$majority}");

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', $expectedDocuments);
        self::assertSelectorTextNotContains('body', $unexpectedDocuments);
    }

    public function testRegistrationIsRefusedForAnAttestationOnAFirstLicense(): void
    {
        [$client] = $this->openRegistration();

        $crawler = $client->submitForm('Valider mon inscription', [
            'registration[user][postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('Valider mon inscription')->form([
            'registration[user][gender]' => 'm',
            'registration[user][firstName]' => 'John',
            'registration[user][lastName]' => 'Doe',
            'registration[user][email]' => 'john.doe@avirontours.fr',
            'registration[user][plainPassword][first]' => 'engage',
            'registration[user][plainPassword][second]' => 'engage',
            'registration[user][nationality]' => 'FR',
            'registration[user][birthday]' => (new \DateTime('-20 years'))->format('Y-m-d'),
            'registration[user][laneNumber]' => '100',
            'registration[user][laneType]' => 'Rue',
            'registration[user][laneName]' => 'du test',
            'registration[user][city]' => 'One City',
            'registration[license][medicalCertificate][type]' => CertificateType::Attestation->value,
            'registration[license][medicalCertificate][level]' => CertificateLevel::Competition->value,
            'registration[license][medicalCertificate][date]' => (new \DateTime())->format('Y-m-d'),
            'registration[agreeSwim]' => 1,
            'registration[agreeRulesAndRegulations]' => 1,
            'registration[agreeMedicalCertificate]' => 1,
        ]);
        $form['registration[license][medicalCertificate][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/document.pdf');
        $crawler = $client->submit($form);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertCount(0, $crawler->filterXPath('//*[@class="alert alert-danger d-block"]'));
        self::assertCount(1, $crawler->filterXPath('//*[@class="invalid-feedback d-block"]'));
        self::assertStringContainsString('un certificat médical est exigé pour une première licence.', $this->filterFormErrors($crawler, 'registration_license_medicalCertificate_type', 'div', 'fieldset')->text());

        UserFactory::repository()->assert()->count(0);
        LicenseFactory::repository()->assert()->count(0);
        MedicalCertificateFactory::repository()->assert()->count(0);
    }

    public function testRegistrationIsRefusedWithoutTheCertificateAttestation(): void
    {
        [$client] = $this->openRegistration();

        $crawler = $client->submitForm('Valider mon inscription', [
            'registration[user][postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('Valider mon inscription')->form([
            'registration[user][gender]' => 'm',
            'registration[user][firstName]' => 'John',
            'registration[user][lastName]' => 'Doe',
            'registration[user][email]' => 'john.doe@avirontours.fr',
            'registration[user][plainPassword][first]' => 'engage',
            'registration[user][plainPassword][second]' => 'engage',
            'registration[user][nationality]' => 'FR',
            'registration[user][birthday]' => (new \DateTime('-20 years'))->format('Y-m-d'),
            'registration[user][laneNumber]' => '100',
            'registration[user][laneType]' => 'Rue',
            'registration[user][laneName]' => 'du test',
            'registration[user][city]' => 'One City',
            'registration[license][medicalCertificate][type]' => CertificateType::Certificate->value,
            'registration[license][medicalCertificate][level]' => CertificateLevel::Competition->value,
            'registration[license][medicalCertificate][date]' => (new \DateTime())->format('Y-m-d'),
            'registration[agreeSwim]' => 1,
            'registration[agreeRulesAndRegulations]' => 1,
        ]);
        $form['registration[license][medicalCertificate][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/document.pdf');
        $crawler = $client->submit($form);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertCount(0, $crawler->filterXPath('//*[@class="alert alert-danger d-block"]'));
        self::assertCount(1, $crawler->filterXPath('//*[@class="invalid-feedback d-block"]'));
        self::assertStringContainsString('Vous devez attester que le document joint est valide pour vous inscrire.', $this->filterFormErrors($crawler, 'registration_agreeMedicalCertificate')->text());

        UserFactory::repository()->assert()->count(0);
        LicenseFactory::repository()->assert()->count(0);
        MedicalCertificateFactory::repository()->assert()->count(0);
    }

    #[DataProvider('provideRegistrationAttestationStates')]
    public function testRegistrationRulesOnTheAttestationFromTheBirthday(?string $birthday, ?string $attestationDisabled, string $expectedHelp): void
    {
        [$client] = $this->openRegistration();
        $crawler = $client->getCrawler();

        self::assertNull($crawler->filter('#registration_license_medicalCertificate_type_0')->attr('disabled'));
        self::assertSame('target', $crawler->filter('#medical-certificate-type')->attr('data-dependent-field-target'));

        if (null !== $birthday) {
            $crawler = $client->submitForm('Valider mon inscription', [
                'registration[user][birthday]' => (new \DateTime($birthday))->format('Y-m-d'),
            ], 'POST', ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

            self::assertResponseStatusCodeSame(Response::HTTP_OK);
            self::assertCount(0, $crawler->filterXPath('//*[@class="alert alert-danger d-block"]'));
            self::assertCount(0, $crawler->filterXPath('//*[@class="invalid-feedback d-block"]'));
        }

        self::assertSame($attestationDisabled, $crawler->filter('#registration_license_medicalCertificate_type_0')->attr('disabled'));
        self::assertNull($crawler->filter('#registration_license_medicalCertificate_type_1')->attr('disabled'));
        self::assertStringContainsString($expectedHelp, $crawler->filter('#registration_license_medicalCertificate_type_help')->text(''));
    }

    public function testRenew(): void
    {
        $requestedSeason = self::currentSeason() + 1;
        $user = UserFactory::new()->major()->create();
        $this->createLicenseForSeason($user, $requestedSeason - 1, CertificateType::Certificate);

        [$client, $seasonCategory] = $this->openRenew($user, $requestedSeason);
        $crawler = $client->getCrawler();

        self::assertCount(0, $crawler->filter('#renew_user_gender_0, #renew_user_firstName, #renew_user_lastName, #renew_user_nationality, #renew_user_birthday'));
        self::assertSame($user->getFirstName(), $crawler->filter('#locked-first-name')->attr('value'));
        self::assertSame($user->getLastName(), $crawler->filter('#locked-last-name')->attr('value'));
        self::assertNotNull($crawler->filter('#locked-first-name')->attr('readonly'));

        $crawler = $client->submitForm('Me réinscrire', [
            'renew[user][postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('Me réinscrire')->form([
            'renew[user][email]' => 'john.doe@avirontours.fr',
            'renew[user][phoneNumber]' => '0102030405',
            'renew[user][laneNumber]' => '100',
            'renew[user][laneType]' => 'Rue',
            'renew[user][laneName]' => 'du test',
            'renew[user][city]' => 'One City',
            'renew[user][clubEmailAllowed]' => 1,
            'renew[user][firstLegalGuardian][role]' => LegalGuardianRole::Father->value,
            'renew[user][firstLegalGuardian][firstName]' => 'Gandalf',
            'renew[user][firstLegalGuardian][lastName]' => 'Le Blanc',
            'renew[user][firstLegalGuardian][email]' => 'g.le-blanc@avirontours.fr',
            'renew[user][firstLegalGuardian][phoneNumber]' => '0123456788',
            'renew[user][secondLegalGuardian][role]' => LegalGuardianRole::Mother->value,
            'renew[user][secondLegalGuardian][firstName]' => 'Galadriel',
            'renew[user][secondLegalGuardian][lastName]' => 'Artanis',
            'renew[user][secondLegalGuardian][email]' => 'g.artanis@avirontours.fr',
            'renew[user][secondLegalGuardian][phoneNumber]' => '0123456799',
            'renew[license][medicalCertificate][type]' => CertificateType::Attestation->value,
            'renew[license][medicalCertificate][level]' => CertificateLevel::Competition->value,
            'renew[license][medicalCertificate][date]' => (new \DateTime())->format('Y-m-d'),
            'renew[license][optionalInsurance]' => 1,
            'renew[license][federationEmailAllowed]' => 1,
            'renew[agreeSwim]' => 1,
            'renew[agreeRulesAndRegulations]' => 1,
            'renew[agreeMedicalCertificate]' => 1,
        ]);
        $form['renew[license][medicalCertificate][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/document.pdf');
        $client->submit($form);

        self::assertResponseRedirects('/renew/confirmation/major');

        self::getEntityManager()->clear();

        $this->assertRegistrationEmail(renew: true, isMajor: true);

        self::assertSame('john.doe@avirontours.fr', $user->getEmail());
        self::assertSame((new \DateTime())->format('Y-m-d'), $user->getSubscriptionDate()->format('Y-m-d'));
        self::assertSame('0102030405', $user->getPhoneNumber());
        self::assertSame('100', $user->getLaneNumber());
        self::assertSame('Rue', $user->getLaneType());
        self::assertSame('Du Test', $user->getLaneName());
        self::assertSame('01000', $user->getPostalCode());
        self::assertSame('One City', $user->getCity());
        self::assertTrue($user->getClubEmailAllowed());
        self::assertSame(LegalGuardianRole::Father, $user->getFirstLegalGuardian()->getRole());
        self::assertSame('Gandalf', $user->getFirstLegalGuardian()->getFirstName());
        self::assertSame('Le Blanc', $user->getFirstLegalGuardian()->getLastName());
        self::assertSame('g.le-blanc@avirontours.fr', $user->getFirstLegalGuardian()->getEmail());
        self::assertSame('0123456788', $user->getFirstLegalGuardian()->getPhoneNumber());
        self::assertSame(LegalGuardianRole::Mother, $user->getSecondLegalGuardian()->getRole());
        self::assertSame('Galadriel', $user->getSecondLegalGuardian()->getFirstName());
        self::assertSame('Artanis', $user->getSecondLegalGuardian()->getLastName());
        self::assertSame('g.artanis@avirontours.fr', $user->getSecondLegalGuardian()->getEmail());
        self::assertSame('0123456799', $user->getSecondLegalGuardian()->getPhoneNumber());

        $license = $user->getLicenses()->last();
        self::assertCount(2, $user->getLicenses());
        self::assertSame($seasonCategory->getId(), $license->getSeasonCategory()->getId());
        self::assertTrue($license->getFederationEmailAllowed());
        self::assertTrue($license->getOptionalInsurance());
        self::assertSame(CertificateType::Attestation, $license->getMedicalCertificate()->getType());
        self::assertSame(CertificateLevel::Competition, $license->getMedicalCertificate()->getLevel());
        self::assertSame((new \DateTime())->format('Y-m-d'), $license->getMedicalCertificate()->getDate()->format('Y-m-d'));
        self::assertNotNull($license->getMedicalCertificate()->getUploadedFile());
        self::assertSame(
            ['wait_medical_certificate_validation' => 1, 'wait_payment_validation' => 1],
            $license->getMarking()
        );

        UserFactory::repository()->assert()->count(1);
        LicenseFactory::repository()->assert()->count(2);
        MedicalCertificateFactory::repository()->assert()->count(2);
    }

    #[DataProvider('provideConfirmationDocuments')]
    public function testRenewConfirmationListsTheDocumentsToReturn(string $majority, string $expectedDocuments, string $unexpectedDocuments): void
    {
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_USER');
        $client->request('GET', "/renew/confirmation/{$majority}");

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', $expectedDocuments);
        self::assertSelectorTextNotContains('body', $unexpectedDocuments);
    }

    #[DataProvider('provideRenewAttestationStates')]
    public function testRenewRulesOnTheAttestationFromTheHistory(
        string $birthday,
        int $seasonsAgo,
        CertificateType $previousType,
        CertificateLevel $previousLevel,
        ?string $attestationDisabled,
        string $expectedHelp,
    ): void {
        $requestedSeason = self::currentSeason() + 1;
        $user = UserFactory::createOne(['birthday' => new \DateTime($birthday)]);
        $this->createLicenseForSeason($user, $requestedSeason - $seasonsAgo, $previousType, $previousLevel);

        [$client] = $this->openRenew($user, $requestedSeason);
        $crawler = $client->getCrawler();

        self::assertSame($attestationDisabled, $crawler->filter('#renew_license_medicalCertificate_type_0')->attr('disabled'));
        self::assertNull($crawler->filter('#renew_license_medicalCertificate_type_1')->attr('disabled'));
        self::assertStringContainsString($expectedHelp, $crawler->filter('#renew_license_medicalCertificate_type_help')->text(''));
    }

    #[DataProvider('provideRenewAttestationLevels')]
    public function testRenewLocksTheAttestationLevelToTheCertificateItExtends(
        string $birthday,
        CertificateLevel $previousLevel,
        ?string $competitionDisabled,
        ?string $practiceDisabled,
        string $expectedHelp,
    ): void {
        $requestedSeason = self::currentSeason() + 1;
        $user = UserFactory::createOne(['birthday' => new \DateTime($birthday)]);
        $this->createLicenseForSeason($user, $requestedSeason - 1, CertificateType::Certificate, $previousLevel);

        [$client] = $this->openRenew($user, $requestedSeason);

        self::assertNull($client->getCrawler()->filter('#renew_license_medicalCertificate_level_0')->attr('disabled'));
        self::assertSame('change->dependent-field#change', $client->getCrawler()->filter('#medical-certificate-type')->attr('data-action'));

        $crawler = $client->submitForm('Me réinscrire', [
            'renew[license][medicalCertificate][type]' => CertificateType::Attestation->value,
        ], 'POST', ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame($competitionDisabled, $crawler->filter('#renew_license_medicalCertificate_level_0')->attr('disabled'));
        self::assertSame($practiceDisabled, $crawler->filter('#renew_license_medicalCertificate_level_1')->attr('disabled'));
        self::assertStringContainsString($expectedHelp, $crawler->filter('#renew_license_medicalCertificate_level_help')->text(''));
    }

    public function testRenewIsRefusedForAnAttestationAtAnotherLevelThanTheCertificate(): void
    {
        $requestedSeason = self::currentSeason() + 1;
        $user = UserFactory::new()->major()->create();
        $this->createLicenseForSeason($user, $requestedSeason - 1, CertificateType::Certificate, CertificateLevel::Practice);

        [$client] = $this->openRenew($user, $requestedSeason);

        $form = $client->getCrawler()->selectButton('Me réinscrire')->form([
            'renew[license][medicalCertificate][type]' => CertificateType::Attestation->value,
            'renew[license][medicalCertificate][level]' => CertificateLevel::Competition->value,
            'renew[license][medicalCertificate][date]' => (new \DateTime())->format('Y-m-d'),
            'renew[agreeSwim]' => 1,
            'renew[agreeRulesAndRegulations]' => 1,
            'renew[agreeMedicalCertificate]' => 1,
        ]);
        $form['renew[license][medicalCertificate][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/document.pdf');
        $crawler = $client->submit($form);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertCount(0, $crawler->filterXPath('//*[@class="alert alert-danger d-block"]'));
        self::assertCount(1, $crawler->filterXPath('//*[@class="invalid-feedback d-block"]'));
        self::assertStringContainsString('Votre certificat médical est de niveau Loisir.', $this->filterFormErrors($crawler, 'renew_license_medicalCertificate_level', 'div', 'fieldset')->text());

        LicenseFactory::repository()->assert()->count(1);
    }

    public function testTheAddressIsARecapOnRenew(): void
    {
        $requestedSeason = self::currentSeason() + 1;
        $user = UserFactory::new()->major()->create();
        $this->createLicenseForSeason($user, $requestedSeason - 1, CertificateType::Certificate);

        [$client] = $this->openRenew($user, $requestedSeason);
        $crawler = $client->getCrawler();

        self::assertStringContainsString('collapse', $crawler->filter('#address-fields')->attr('class'));
        self::assertStringNotContainsString('show', $crawler->filter('#address-fields')->attr('class'));
        self::assertCount(1, $crawler->filter('button[data-bs-target="#address-fields"]'));
        self::assertCount(1, $crawler->filter('#address-fields #renew_user_laneName'));
        self::assertStringContainsString($user->getLaneName(), $crawler->filter('form')->text());
    }

    public function testTheAddressRecapReopensOnItsOwnErrorsOnRenew(): void
    {
        $requestedSeason = self::currentSeason() + 1;
        $user = UserFactory::new()->major()->create();
        $this->createLicenseForSeason($user, $requestedSeason - 1, CertificateType::Certificate);

        [$client] = $this->openRenew($user, $requestedSeason);

        $crawler = $client->submitForm('Me réinscrire', [
            'renew[user][laneName]' => '',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertCount(0, $crawler->filterXPath('//*[@class="alert alert-danger d-block"]'));
        self::assertCount(1, $crawler->filterXPath('//*[@id="address-fields"]//*[@class="invalid-feedback d-block"]'));
        self::assertStringContainsString('Cette valeur ne doit pas être vide.', $this->filterFormErrors($crawler, 'renew_user_laneName')->text());
        self::assertStringContainsString('show', $crawler->filter('#address-fields')->attr('class'));
        self::assertSame('true', $crawler->filter('button[data-bs-target="#address-fields"]')->attr('aria-expanded'));
    }

    public function testLegalGuardiansSectionIsMarkedMinorForAMinorOnRenew(): void
    {
        $user = UserFactory::new()->minor()->create();
        LicenseFactory::createOne(['user' => $user]);

        [$client] = $this->openRenew($user, self::currentSeason());
        $crawler = $client->getCrawler();

        self::assertCount(0, $crawler->filter('#renew_user_birthday'));
        self::assertSame('true', $crawler->filter('[data-controller~="legal-guardians"]')->attr('data-legal-guardians-minor-value'));
    }

    public function testSecondLegalGuardianIsVisibleWhenAlreadyFilledOnRenew(): void
    {
        $user = UserFactory::new()->minor()->create([
            'secondLegalGuardian' => (new LegalGuardian())
                ->setRole(LegalGuardianRole::Mother)
                ->setFirstName('Galadriel')
                ->setLastName('Artanis')
                ->setEmail('g.artanis@avirontours.fr')
                ->setPhoneNumber('0123456799'),
        ]);
        LicenseFactory::createOne(['user' => $user]);

        [$client] = $this->openRenew($user, self::currentSeason());
        $crawler = $client->getCrawler();

        self::assertStringNotContainsString('d-none', $crawler->filter('#second-legal-guardian')->attr('class'));
        self::assertStringContainsString('d-none', $crawler->filter('[data-legal-guardians-target="addButton"]')->attr('class'));
    }

    public function testClubEmailConsentIsPreservedOnRenew(): void
    {
        $user = UserFactory::createOne(['clubEmailAllowed' => true]);
        LicenseFactory::createOne(['user' => $user]);

        [$client] = $this->openRenew($user, self::currentSeason());

        self::assertNotNull($client->getCrawler()->filter('#renew_user_clubEmailAllowed')->attr('checked'));
    }

    public function testTheOptionalInsuranceSitsInTheRecapAndDrivesTheTotal(): void
    {
        [$client, $seasonCategory] = $this->openRegistration();
        $crawler = $client->getCrawler();

        self::assertCount(1, $crawler->filter('#price-recap #registration_license_optionalInsurance'));
        self::assertSame('target', $crawler->filter('#price-recap')->attr('data-dependent-field-target'));
        self::assertStringContainsString('Non souscrite', $crawler->filter('#price-recap')->text());
        self::assertStringContainsString(
            number_format($seasonCategory->getPrice(), 2, ',', ' '),
            $crawler->filter('#price-recap li:last-child')->text()
        );

        self::assertNull($crawler->filter('#price-recap')->attr('aria-live'));
        self::assertCount(1, $crawler->filter('[aria-live="polite"] > #price-recap'));

        $crawler = $client->submitForm('Valider mon inscription', [
            'registration[license][optionalInsurance]' => 1,
        ], 'POST', ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        $total = number_format($seasonCategory->getPrice() + License::OPTIONAL_INSURANCE_PRICE, 2, ',', ' ');
        self::assertStringContainsString($total, $crawler->filter('#price-recap li:last-child')->text());
        self::assertStringContainsString($total, $crawler->filter('#sticky-total')->text());
    }

    public function testLegalGuardiansSectionIsCollapsedAndDrivenByTheController(): void
    {
        [$client] = $this->openRegistration();
        $crawler = $client->getCrawler();

        $controller = $crawler->filter('[data-controller~="legal-guardians"]');
        self::assertSame('false', $controller->attr('data-legal-guardians-minor-value'));
        self::assertSame(
            (new \DateTime('-18 years'))->format('Y-m-d'),
            $controller->attr('data-legal-guardians-majority-date-value')
        );

        self::assertStringContainsString(
            'legal-guardians#checkBirthday',
            $crawler->filter('#registration_user_birthday')->attr('data-action')
        );

        self::assertCount(1, $crawler->filter('#legal-guardians[data-legal-guardians-target="section"]'));
        self::assertStringNotContainsString('show', $crawler->filter('#legal-guardians')->attr('class'));
        self::assertCount(1, $crawler->filter('#registration_user_firstLegalGuardian_firstName'));

        self::assertStringContainsString('d-none', $crawler->filter('#second-legal-guardian')->attr('class'));
        self::assertCount(1, $crawler->filter('#registration_user_secondLegalGuardian_firstName'));
        self::assertStringNotContainsString(
            'd-none',
            $crawler->filter('[data-legal-guardians-target="addButton"]')->attr('class')
        );

        self::assertSame('', $crawler->filter('[data-legal-guardians-target="badge"]')->text());
        self::assertCount(2, $crawler->filter('[data-legal-guardians-target~="guardian"]'));
    }

    public function testRegistrationAsLogInUser(): void
    {
        $season = SeasonFactory::new()->subscriptionEnabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_USER');
        $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        self::assertResponseRedirects('/profile');
    }

    public function testNonEnabledRegistration(): void
    {
        $season = SeasonFactory::new()->subscriptionDisabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testNonDisplayedCategoryRegistration(): void
    {
        $season = SeasonFactory::new()->subscriptionEnabled()->seasonCategoriesNotDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testRenewAsAnonymousUser(): void
    {
        $season = SeasonFactory::new()->subscriptionDisabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/renew/'.$season->getSeasonCategories()->first()->getSlug());

        self::assertResponseRedirects('/login');
    }

    public function testNonEnabledRenew(): void
    {
        $season = SeasonFactory::new()->subscriptionDisabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_USER');
        $client->request('GET', '/renew/'.$season->getSeasonCategories()->first()->getSlug());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return array{KernelBrowser, SeasonCategory}
     */
    private function openRegistration(): array
    {
        PostalCodeFactory::createOne([
            'postalCode' => '01000',
            'city' => 'One City',
        ]);
        $season = SeasonFactory::new()->subscriptionEnabled()->seasonCategoriesDisplayed()->create();
        $seasonCategory = $season->getSeasonCategories()->first();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/register/'.$seasonCategory->getSlug());

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        return [$client, $seasonCategory];
    }

    /**
     * @return array{KernelBrowser, SeasonCategory}
     */
    private function openRenew(User $user, int $requestedSeason): array
    {
        PostalCodeFactory::createOne([
            'postalCode' => '01000',
            'city' => 'One City',
        ]);
        $season = SeasonFactory::new()->subscriptionEnabled()->seasonCategoriesDisplayed()->create(['name' => $requestedSeason]);
        $seasonCategory = $season->getSeasonCategories()->first();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/renew/'.$seasonCategory->getSlug());

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        return [$client, $seasonCategory];
    }

    private function createLicenseForSeason(User $user, int $seasonYear, CertificateType $type, CertificateLevel $level = CertificateLevel::Competition): void
    {
        $season = SeasonFactory::new()->subscriptionDisabled()->create(['name' => $seasonYear]);

        LicenseFactory::createOne([
            'user' => $user,
            'seasonCategory' => SeasonCategoryFactory::createOne(['season' => $season]),
            'medicalCertificate' => MedicalCertificateFactory::createOne([
                'type' => $type,
                'level' => $level,
                'date' => new \DateTime(\sprintf('%d-12-01', $seasonYear - 1)),
            ]),
        ]);
    }

    /**
     * @param list<string> $legalGuardianEmails
     */
    private function assertRegistrationEmail(bool $renew, bool $isMajor, array $legalGuardianEmails = []): void
    {
        self::assertQueuedEmailCount(1);

        $email = $this->getMailerMessage();
        $subject = $renew ? 'Votre réinscription' : 'Votre inscription';
        self::assertEmailHeaderSame($email, 'Subject', "{$subject} à l'Aviron Tours Métropole : les étapes pour la finaliser");
        self::assertEmailAddressContains($email, 'To', 'john.doe@avirontours.fr');
        self::assertEmailAddressContains($email, 'Reply-To', 'contact@avirontours.fr');
        self::assertEmailHtmlBodyContains($email, $renew ? 'vous remercie de votre réinscription' : 'vous souhaite la bienvenue');

        self::assertCount(\count($legalGuardianEmails), $email->getCc());
        foreach ($legalGuardianEmails as $legalGuardianEmail) {
            self::assertEmailAddressContains($email, 'Cc', $legalGuardianEmail);
        }

        $attachments = $email->getAttachments();
        self::assertStringEqualsFile(__DIR__.'/../../public/files/droit-image.pdf', $attachments[0]->getBody());

        if ($isMajor) {
            self::assertEmailHtmlBodyContains($email, "nous retourner l'attestation de droit à l'image, jointe à cet email, datée et signée");
            self::assertEmailAttachmentCount($email, 1);

            return;
        }

        self::assertEmailHtmlBodyContains($email, 'signées par le représentant légal');
        self::assertEmailAttachmentCount($email, 3);
        self::assertStringEqualsFile(__DIR__.'/../../public/files/autorisation-parentale.pdf', $attachments[1]->getBody());
        self::assertStringEqualsFile(__DIR__.'/../../public/files/fiche-sanitaire.pdf', $attachments[2]->getBody());
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideConfirmationDocuments(): iterable
    {
        yield 'majeur' => ['major', "nous retourner l'attestation de droit à l'image, jointe à l'email de confirmation, datée et signée", 'représentant légal'];
        yield 'mineur' => ['minor', "nous retourner l'attestation de droit à l'image, l'autorisation parentale et la fiche de liaison sanitaire, jointes à l'email de confirmation, complétées et signées par le représentant légal", 'datée et signée'];
    }

    /**
     * @return iterable<string, array{?string, ?string, string}>
     */
    public static function provideRegistrationAttestationStates(): iterable
    {
        yield 'sans date de naissance' => [null, null, 'Renseignez votre date de naissance'];
        yield 'mineur' => ['-15 years', null, 'acceptée pour les personnes mineures'];
        yield 'majeur' => ['-20 years', 'disabled', 'ne vaut qu\'en renouvellement'];
    }

    /**
     * @return iterable<string, array{string, int, CertificateType, CertificateLevel, ?string, string}>
     */
    public static function provideRenewAttestationStates(): iterable
    {
        yield 'mineur' => [
            '-15 years', 1, CertificateType::Certificate, CertificateLevel::Competition,
            null, 'acceptée pour les personnes mineures',
        ];
        yield 'majeur, aucun certificat depuis la majorité' => [
            '-40 years', 1, CertificateType::Attestation, CertificateLevel::Competition,
            'disabled', 'Vous n\'avez pas encore fourni de certificat médical en tant que majeur',
        ];
        yield 'majeur, certificat Loisir de la saison précédente' => [
            '-40 years', 1, CertificateType::Certificate, CertificateLevel::Practice,
            null, 'dont elle conserve le niveau: Loisir.',
        ];
        yield 'majeur, certificat Compétition de la saison précédente' => [
            '-40 years', 1, CertificateType::Certificate, CertificateLevel::Competition,
            null, 'dont elle conserve le niveau: Compétition.',
        ];
        yield 'majeur, une saison manquante depuis le certificat' => [
            '-40 years', 2, CertificateType::Certificate, CertificateLevel::Practice,
            'disabled', 'Votre inscription au club a été interrompue',
        ];
        yield 'majeur, certificat Compétition périmé' => [
            '-40 years', 4, CertificateType::Certificate, CertificateLevel::Competition,
            'disabled', 'expire le',
        ];
    }

    /**
     * @return iterable<string, array{string, CertificateLevel, ?string, ?string, string}>
     */
    public static function provideRenewAttestationLevels(): iterable
    {
        yield 'certificat Loisir' => [
            '-40 years', CertificateLevel::Practice,
            'disabled', null, 'Repris du certificat médical que votre attestation prolonge',
        ];
        yield 'certificat Compétition' => [
            '-40 years', CertificateLevel::Competition,
            null, 'disabled', 'Repris du certificat médical que votre attestation prolonge',
        ];
        yield 'membre mineur' => [
            '-15 years', CertificateLevel::Competition,
            null, null, 'Compétition pour participer aux régates',
        ];
    }

    private static function currentSeason(): int
    {
        $now = new \DateTime();

        return (int) $now->format('Y') + (9 <= (int) $now->format('n') ? 1 : 0);
    }
}
