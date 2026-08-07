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

namespace App\Tests\Validator;

use App\Entity\License;
use App\Entity\MedicalCertificate;
use App\Entity\Season;
use App\Entity\SeasonCategory;
use App\Entity\User;
use App\Enum\CertificateLevel;
use App\Enum\CertificateType;
use App\Validator\Attestation;
use App\Validator\AttestationValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class AttestationValidatorTest extends ConstraintValidatorTestCase
{
    private const int REQUESTED_YEAR = 2027;

    #[DataProvider('provideLevels')]
    public function testAMinorCouldAlwaysSupplyAnAttestation(CertificateLevel $level): void
    {
        $user = $this->createUser(15);
        $license = $this->createLicense($user, self::REQUESTED_YEAR, CertificateType::Attestation, $level);

        $this->validate($license, new Attestation());

        $this->assertNoViolation();
    }

    #[DataProvider('provideLevels')]
    public function testAMinorIsNotTiedToAPreviousCertificateLevel(CertificateLevel $level): void
    {
        $user = $this->createUser(15);
        $this->addPreviousLicense($user, 1, CertificateType::Certificate, new \DateTime('-1 year'), CertificateLevel::Competition);

        $license = $this->createLicense($user, self::REQUESTED_YEAR, CertificateType::Attestation, $level);

        $this->validate($license, new Attestation());

        $this->assertNoViolation();
    }

    #[DataProvider('provideLevels')]
    public function testAnAdultFirstLicenseRequiresACertificate(CertificateLevel $level): void
    {
        $user = $this->createUser(30);
        $license = $this->createLicense($user, self::REQUESTED_YEAR, CertificateType::Attestation, $level);

        $this->validate($license, new Attestation());

        $this->buildViolation('Une attestation QS-Sport n\'est acceptée qu\'en renouvellement: un certificat médical est exigé pour une première licence.')
            ->atPath('property.path.medicalCertificate.type')
            ->assertRaised()
        ;
    }

    #[DataProvider('provideLevels')]
    public function testAMemberWhoOnlyEverSuppliedAttestationsNeedsOneOnReachingMajority(CertificateLevel $level): void
    {
        $user = $this->createUser(18);
        $this->addPreviousLicense($user, 3, CertificateType::Attestation, new \DateTime('-3 years'));
        $this->addPreviousLicense($user, 2, CertificateType::Attestation, new \DateTime('-2 years'));
        $this->addPreviousLicense($user, 1, CertificateType::Attestation, new \DateTime('-1 year'));

        $license = $this->createLicense($user, self::REQUESTED_YEAR, CertificateType::Attestation, $level);

        $this->validate($license, new Attestation());

        $this->buildViolation('Vous atteignez la majorité: un premier certificat médical en tant que majeur est exigé, une attestation QS-Sport ne suffit plus.')
            ->atPath('property.path.medicalCertificate.type')
            ->assertRaised()
        ;
    }

    #[DataProvider('provideLevels')]
    public function testACertificateObtainedAsAMinorDoesNotCountOnceMajorityIsReached(CertificateLevel $level): void
    {
        $user = $this->createUser(18, 6);
        $this->addPreviousLicense($user, 2, CertificateType::Attestation, new \DateTime('-2 years'));
        $this->addPreviousLicense($user, 1, CertificateType::Certificate, new \DateTime('-18 months'));

        $license = $this->createLicense($user, self::REQUESTED_YEAR, CertificateType::Attestation, $level);

        $this->validate($license, new Attestation());

        $this->buildViolation('Vous atteignez la majorité: un premier certificat médical en tant que majeur est exigé, une attestation QS-Sport ne suffit plus.')
            ->atPath('property.path.medicalCertificate.type')
            ->assertRaised()
        ;
    }

    public function testACertificateObtainedAfterMajorityCounts(): void
    {
        $user = $this->createUser(19, 6);
        $this->addPreviousLicense($user, 2, CertificateType::Attestation, new \DateTime('-2 years'));
        $this->addPreviousLicense($user, 1, CertificateType::Certificate, new \DateTime('-6 months'), CertificateLevel::Practice);

        $license = $this->createLicense($user, self::REQUESTED_YEAR, CertificateType::Attestation, CertificateLevel::Practice);

        $this->validate($license, new Attestation());

        $this->assertNoViolation();
    }

    public function testACompetitionAttestationIsRefusedOnAPracticeCertificate(): void
    {
        $user = $this->createUser(30);
        $this->addPreviousLicense($user, 1, CertificateType::Certificate, new \DateTime('-1 year'), CertificateLevel::Practice);

        $license = $this->createLicense($user, self::REQUESTED_YEAR, CertificateType::Attestation, CertificateLevel::Competition);

        $this->validate($license, new Attestation());

        $this->buildViolation('Votre certificat médical est de niveau {{ level }}.')
            ->setParameter('{{ level }}', 'Loisir')
            ->atPath('property.path.medicalCertificate.level')
            ->assertRaised()
        ;
    }

    public function testAPracticeAttestationIsRefusedOnACompetitionCertificate(): void
    {
        $user = $this->createUser(30);
        $this->addPreviousLicense($user, 1, CertificateType::Certificate, new \DateTime('-1 year'), CertificateLevel::Competition);

        $license = $this->createLicense($user, self::REQUESTED_YEAR, CertificateType::Attestation, CertificateLevel::Practice);

        $this->validate($license, new Attestation());

        $this->buildViolation('Votre certificat médical est de niveau {{ level }}.')
            ->setParameter('{{ level }}', 'Compétition')
            ->atPath('property.path.medicalCertificate.level')
            ->assertRaised()
        ;
    }

    public function testALapsedCompetitionCertificateIsReportedOverTheLevelItWouldImpose(): void
    {
        $user = $this->createUser(30);
        $date = new \DateTime('-4 years');
        $this->addPreviousLicense($user, 4, CertificateType::Certificate, $date);
        $this->addPreviousLicense($user, 3, CertificateType::Attestation, new \DateTime('-3 years'));
        $this->addPreviousLicense($user, 2, CertificateType::Attestation, new \DateTime('-2 years'));
        $this->addPreviousLicense($user, 1, CertificateType::Attestation, new \DateTime('-1 year'));

        $license = $this->createLicense($user, self::REQUESTED_YEAR, CertificateType::Attestation, CertificateLevel::Practice);

        $this->validate($license, new Attestation());

        $this->buildViolation('Une licence compétition exige un certificat médical valable 3 ans, encore valide lors de la transmission de votre licence à la fédération. Le vôtre, daté du {{ date }}, expire le {{ expiryDate }}. Fournissez un certificat médical plus récent.')
            ->setParameter('{{ date }}', $date->format('d/m/Y'))
            ->setParameter('{{ expiryDate }}', (clone $date)->modify('+3 years')->format('d/m/Y'))
            ->atPath('property.path.medicalCertificate.type')
            ->assertRaised()
        ;
    }

    public function testALapsedCompetitionCertificateIsReportedOverAnEnrolmentGap(): void
    {
        $user = $this->createUser(30);
        $date = new \DateTime('-4 years');
        $this->addPreviousLicense($user, 4, CertificateType::Certificate, $date);

        $license = $this->createLicense($user, self::REQUESTED_YEAR, CertificateType::Attestation, CertificateLevel::Competition);

        $this->validate($license, new Attestation());

        $this->buildViolation('Une licence compétition exige un certificat médical valable 3 ans, encore valide lors de la transmission de votre licence à la fédération. Le vôtre, daté du {{ date }}, expire le {{ expiryDate }}. Fournissez un certificat médical plus récent.')
            ->setParameter('{{ date }}', $date->format('d/m/Y'))
            ->setParameter('{{ expiryDate }}', (clone $date)->modify('+3 years')->format('d/m/Y'))
            ->atPath('property.path.medicalCertificate.type')
            ->assertRaised()
        ;
    }

    public function testAnInterruptedEnrolmentIsReportedOverTheLevelItWouldImpose(): void
    {
        $user = $this->createUser(30);
        $this->addPreviousLicense($user, 2, CertificateType::Certificate, new \DateTime('-2 years'), CertificateLevel::Competition);

        $license = $this->createLicense($user, self::REQUESTED_YEAR, CertificateType::Attestation, CertificateLevel::Practice);

        $this->validate($license, new Attestation());

        $this->buildViolation('Votre inscription au club a été interrompue (saison {{ season }} manquante) depuis votre dernier certificat médical: un nouveau certificat médical est exigé.')
            ->setParameter('{{ season }}', (string) (self::REQUESTED_YEAR - 1))
            ->atPath('property.path.medicalCertificate.type')
            ->assertRaised()
        ;
    }

    public function testARecentCertificateAllowsACompetitionAttestation(): void
    {
        $user = $this->createUser(30);
        $this->addPreviousLicense($user, 1, CertificateType::Certificate, new \DateTime('-1 year'));

        $license = $this->createLicense($user, self::REQUESTED_YEAR, CertificateType::Attestation, CertificateLevel::Competition);

        $this->validate($license, new Attestation());

        $this->assertNoViolation();
    }

    public function testACompetitionLicenseNeedsACertificateEveryThreeYears(): void
    {
        $user = $this->createUser(30);
        $date = new \DateTime('-4 years');
        $this->addPreviousLicense($user, 4, CertificateType::Certificate, $date);
        $this->addPreviousLicense($user, 3, CertificateType::Attestation, new \DateTime('-3 years'));
        $this->addPreviousLicense($user, 2, CertificateType::Attestation, new \DateTime('-2 years'));
        $this->addPreviousLicense($user, 1, CertificateType::Attestation, new \DateTime('-1 year'));

        $license = $this->createLicense($user, self::REQUESTED_YEAR, CertificateType::Attestation, CertificateLevel::Competition);

        $this->validate($license, new Attestation());

        $this->buildViolation('Une licence compétition exige un certificat médical valable 3 ans, encore valide lors de la transmission de votre licence à la fédération. Le vôtre, daté du {{ date }}, expire le {{ expiryDate }}. Fournissez un certificat médical plus récent.')
            ->setParameter('{{ date }}', $date->format('d/m/Y'))
            ->setParameter('{{ expiryDate }}', (clone $date)->modify('+3 years')->format('d/m/Y'))
            ->atPath('property.path.medicalCertificate.type')
            ->assertRaised()
        ;
    }

    public function testACertificateExpiringAfterTheFederalSyncMarginIsAccepted(): void
    {
        $user = $this->createUser(30);
        $this->addPreviousLicense($user, 1, CertificateType::Certificate, new \DateTime('-3 years +2 months'));

        $license = $this->createLicense($user, self::REQUESTED_YEAR, CertificateType::Attestation, CertificateLevel::Competition);

        $this->validate($license, new Attestation());

        $this->assertNoViolation();
    }

    public function testACertificateExpiringWithinTheFederalSyncMarginIsRefused(): void
    {
        $user = $this->createUser(30);
        $date = new \DateTime('-3 years +15 days');
        $this->addPreviousLicense($user, 1, CertificateType::Certificate, $date);

        $license = $this->createLicense($user, self::REQUESTED_YEAR, CertificateType::Attestation, CertificateLevel::Competition);

        $this->validate($license, new Attestation());

        $this->buildViolation('Une licence compétition exige un certificat médical valable 3 ans, encore valide lors de la transmission de votre licence à la fédération. Le vôtre, daté du {{ date }}, expire le {{ expiryDate }}. Fournissez un certificat médical plus récent.')
            ->setParameter('{{ date }}', $date->format('d/m/Y'))
            ->setParameter('{{ expiryDate }}', (clone $date)->modify('+3 years')->format('d/m/Y'))
            ->atPath('property.path.medicalCertificate.type')
            ->assertRaised()
        ;
    }

    public function testAPracticeLicenseHasNoThreeYearCycle(): void
    {
        $user = $this->createUser(30);
        $this->addPreviousLicense($user, 4, CertificateType::Certificate, new \DateTime('-4 years'), CertificateLevel::Practice);
        $this->addPreviousLicense($user, 3, CertificateType::Attestation, new \DateTime('-3 years'));
        $this->addPreviousLicense($user, 2, CertificateType::Attestation, new \DateTime('-2 years'));
        $this->addPreviousLicense($user, 1, CertificateType::Attestation, new \DateTime('-1 year'));

        $license = $this->createLicense($user, self::REQUESTED_YEAR, CertificateType::Attestation, CertificateLevel::Practice);

        $this->validate($license, new Attestation());

        $this->assertNoViolation();
    }

    #[DataProvider('provideLevels')]
    public function testAnInterruptedEnrolmentRequiresANewCertificate(CertificateLevel $level): void
    {
        $user = $this->createUser(30);
        $this->addPreviousLicense($user, 2, CertificateType::Certificate, new \DateTime('-2 years'), $level);

        $license = $this->createLicense($user, self::REQUESTED_YEAR, CertificateType::Attestation, $level);

        $this->validate($license, new Attestation());

        $this->buildViolation('Votre inscription au club a été interrompue (saison {{ season }} manquante) depuis votre dernier certificat médical: un nouveau certificat médical est exigé.')
            ->setParameter('{{ season }}', (string) (self::REQUESTED_YEAR - 1))
            ->atPath('property.path.medicalCertificate.type')
            ->assertRaised()
        ;
    }

    public function testItIgnoresALicenseWithoutABirthday(): void
    {
        $license = $this->createLicense(new User(), self::REQUESTED_YEAR, CertificateType::Attestation, CertificateLevel::Competition);

        $this->validate($license, new Attestation());

        $this->assertNoViolation();
    }

    public function testItIgnoresANullValue(): void
    {
        $this->validate(null, new Attestation());

        $this->assertNoViolation();
    }

    #[\Override]
    protected function createValidator(): AttestationValidator
    {
        return new AttestationValidator();
    }

    private function createUser(int $yearsAgo, int $monthsAgo = 0): User
    {
        $user = new User();
        $user->setBirthday(new \DateTime(\sprintf('-%d years -%d months', $yearsAgo, $monthsAgo)));

        return $user;
    }

    private function createLicense(User $user, int $seasonYear, CertificateType $type, CertificateLevel $level, ?\DateTime $date = null): License
    {
        $season = new Season()->setName($seasonYear);
        $seasonCategory = new SeasonCategory()
            ->setName('Adulte')
            ->setLicenseType(SeasonCategory::LICENSE_TYPE_ANNUAL)
            ->setSeason($season)
        ;

        $certificate = new MedicalCertificate()
            ->setType($type)
            ->setLevel($level)
            ->setDate($date ?? new \DateTime())
        ;

        return new License($seasonCategory)
            ->setUser($user)
            ->setMedicalCertificate($certificate)
        ;
    }

    private function addPreviousLicense(User $user, int $yearsAgo, CertificateType $type, \DateTime $date, CertificateLevel $level = CertificateLevel::Competition): void
    {
        $license = $this->createLicense($user, self::REQUESTED_YEAR - $yearsAgo, $type, $level, $date);

        $user->addLicense($license);
    }

    public static function provideLevels(): iterable
    {
        yield 'compétition' => [CertificateLevel::Competition];
        yield 'loisir' => [CertificateLevel::Practice];
    }
}
