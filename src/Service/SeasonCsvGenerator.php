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

namespace App\Service;

use App\Entity\License;
use App\Entity\MedicalCertificate;
use App\Entity\Season;
use App\Repository\LicenseRepository;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Serializer;

class SeasonCsvGenerator
{
    public function __construct(private LicenseRepository $licenseRepository)
    {
    }

    public function exportContacts(Season $season): ?string
    {
        $licenses = $this->licenseRepository->findBySeason($season);

        if (empty($licenses)) {
            return null;
        }

        $data = [];
        foreach ($licenses as $license) {
            $data[] = [
                'fullName' => $license->getUser()->getFullName(),
                'email' => $license->getUser()->getEmail(),
                'clubEmailAllowed' => $license->getUser()->getClubEmailAllowed() ? 'Oui' : 'Non',
                'partnerEmailAllowed' => $license->getUser()->getPartnersEmailAllowed() ? 'Oui' : 'Non',
            ];
        }

        $serializer = new Serializer([], [new CsvEncoder()]);

        return $serializer->serialize($data, 'csv', [CsvEncoder::DELIMITER_KEY => ';']);
    }

    public function exportLicenses(Season $season): ?string
    {
        $licenses = $this->licenseRepository->findBySeason($season, true);

        if (empty($licenses)) {
            return null;
        }

        $data = [];
        foreach ($licenses as $license) {
            $user = $license->getUser();

            $data[] = [
                'CodeAdherent' => $user->getLicenseNumber(),
                'Civilité' => $user->getTextCivility(),
                'NomUsage' => $user->getLastName(),
                'Prenom' => $user->getFirstName(),
                'Prenom2' => '',
                'Prenom3' => '',
                'NomNaissance' => '',
                'Nationalite' => 'FR',
                'DateNaissance' => $user->getBirthday()->format('d/m/Y'),
                'PaysNaissance' => '',
                'DeptNaissance' => '',
                'VilleNaissance' => '',
                'NomPere' => '',
                'PrenomPere' => '',
                'NomMere' => '',
                'PrenomMere' => '',
                'NumeroVoie' => $user->getLaneNumber(),
                'TypeVoie' => $user->getLaneType(),
                'LibelleVoie' => $user->getLaneName(),
                'ImmBatRes' => '',
                'AptEtageEsc' => '',
                'Lieudit' => '',
                'Cp' => $user->getPostalCode(),
                'Ville' => $user->getCity(),
                'Pays' => 'FR',
                'Telephone' => '',
                'Autre telephone' => '',
                'Mobile' => '',
                'AutreMobile' => '',
                'Email' => $user->getEmail(),
                'AutreEmail' => '',
                'Fax' => '',
                'UtilisationAdresse' => $license->getFederationEmailAllowed() ? 'Oui' : 'Non',
                'SituationFamille' => '',
                'Profession' => '',
                'CategSocioPro' => '',
                'DateSouscription' => (new \DateTime())->format('d/m/Y'),
                'TypeLicence' => $license->getSeasonCategory()->getLicenseType(),
                'code manifestation' => '',
                'AssuranceIASportPlus' => 'Non',
                'Date certificat "Pratique"' => $this->getMedicalCertificateDate($license, MedicalCertificate::LEVEL_PRACTICE),
                'Medecin certificat "Pratique"' => '',
                'N° Medecin du certificat "Pratique"' => '',
                'Attestation santé "pratique"' => $this->getAttestationValue($license, MedicalCertificate::LEVEL_PRACTICE),
                'Date certificat "Compétition"' => $this->getMedicalCertificateDate($license, MedicalCertificate::LEVEL_COMPETITION),
                'Medecin certificat "Compétition"' => '',
                'N° Medecin du certificat "Compétition"' => '',
                'Attestation santé "compétition"' => $this->getAttestationValue($license, MedicalCertificate::LEVEL_COMPETITION),
                'Date certificat "Surclassement"' => $this->getMedicalCertificateDate($license, MedicalCertificate::LEVEL_UPGRADE),
                'Medecin certificat "Surclassement"' => '',
                'N° Medecin du certificat "Surclassement"' => '',
                'Entreprise' => 'Non',
                'Nom Entreprise' => '',
                'Pratiquant' => 'Oui',
            ];
        }

        $serializer = new Serializer([], [new CsvEncoder()]);

        // the FFA server want a CSV without enclosure, set a special enclosure, then remove it
        $csv = $serializer->serialize($data, 'csv', [CsvEncoder::DELIMITER_KEY => ';', CsvEncoder::ENCLOSURE_KEY => \chr(127)]);

        return str_replace(\chr(127), '', $csv);
    }

    private function getMedicalCertificateDate(License $license, string $level): string
    {
        // Check the level
        if ($level !== $license->getMedicalCertificate()->getLevel()) {
            return '';
        }

        // If this is a Certificate
        if (MedicalCertificate::TYPE_CERTIFICATE === $license->getMedicalCertificate()->getType()) {
            return $license->getMedicalCertificate()->getDate()->format('d/m/Y');
        }

        // Else, this is an attestation: we must return the date of the latest Certificate we have
        /** @var License|bool $latestLicenceWithCertificate */
        $latestLicenceWithCertificate = $license->getUser()->getLicenses()->filter(function (License $license) {
            return MedicalCertificate::TYPE_CERTIFICATE === $license->getMedicalCertificate()->getType();
        })->last();

        // If we do not retrieve a licence with a medical certificate
        // 1. For user over or equal to 18 years old, return a mistake
        // 2. For user less than 18 yo, it's ok, return nothing
        if (false === $latestLicenceWithCertificate) {
            return 18 > $license->getUser()->getAge() ? '' : '??????';
        }

        return $latestLicenceWithCertificate->getMedicalCertificate()->getDate()->format('d/m/Y');
    }

    private function getAttestationValue(License $license, string $level): string
    {
        // Check the level
        if ($level !== $license->getMedicalCertificate()->getLevel()) {
            return '';
        }

        return MedicalCertificate::TYPE_ATTESTATION === $license->getMedicalCertificate()->getType() ? 'Oui' : 'Non';
    }
}
