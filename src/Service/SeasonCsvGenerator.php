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

namespace App\Service;

use App\Entity\Season;
use App\Repository\LicenseRepository;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Serializer;

class SeasonCsvGenerator
{
    private $licenseRepository;

    public function __construct(LicenseRepository $licenseRepository)
    {
        $this->licenseRepository = $licenseRepository;
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
                'CodeAdherent' => '',
                'Civilité' => $user->getTextCivility(),
                'Nom' => $user->getLastName(),
                'Prenom' => $user->getFirstName(),
                'NomJeuneFille' => '',
                'Nationalite' => 'FR',
                'DateNaissance' => $user->getBirthday()->format('d/m/Y'),
                'LieuNaissance' => '',
                'NumeroVoie' => '',
                'TypeVoie' => '',
                'LibelleVoie' => '',
                'ImmBatRes' => '',
                'AptEtageEsc' => '',
                'Lieudit' => '',
                'Cp' => $user->getPostalCode(),
                'Ville' => $user->getCity(),
                'Pays' => 'FRANCE',
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
                'DateSouscription' => $license->getCreatedAt()->format('d/m/Y'),
                'TypeLicence' => $license->getSeasonCategory()->getLicenseType(),
                'code manifestation' => '',
                'AssuranceIASportPlus' => 'Oui',
                'Date certificat "Pratique"' => '',
                'Medecin certificat "Pratique"' => '',
                'Attestation santé "pratique"' => '',
                'Date certificat "Compétition"' => '',
                'Medecin certificat "Compétition"' => '',
                'N° Medecin certificat "Compétition"' => '',
                'Attestation santé "compétition"' => '',
                'Date certificat "Surclassement"' => '',
                'Medecin certificat "Surclassement"' => '',
                'N° Medecin certificat "Surclassement"' => '',
                'Entreprise' => 'Non',
                'Nom Entreprise' => '',
                'Pratiquant' => '',
            ];
        }

        $serializer = new Serializer([], [new CsvEncoder()]);

        return $serializer->serialize($data, 'csv', [CsvEncoder::DELIMITER_KEY => ';']);
    }
}
