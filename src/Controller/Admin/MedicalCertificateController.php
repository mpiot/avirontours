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

namespace App\Controller\Admin;

use App\Entity\MedicalCertificate;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Handler\DownloadHandler;

/**
 * @Security("is_granted('ROLE_USER_ADMIN')")
 */
#[Route(path: '/medical-certificate')]
class MedicalCertificateController extends AbstractController
{
    #[Route(path: '/{id}/dowload', name: 'medical_certificate_download', methods: ['GET'])]
    public function download(MedicalCertificate $medicalCertificate, DownloadHandler $downloadHandler): Response
    {
        return $downloadHandler->downloadObject($medicalCertificate, $fileField = 'file', null, null, false);
    }
}
