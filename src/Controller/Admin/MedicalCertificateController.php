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

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\MedicalCertificate;
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/admin/medical-certificate')]
#[IsGranted('ROLE_SEASON_MEDICAL_CERTIFICATE_ADMIN')]
class MedicalCertificateController extends AbstractController
{
    #[Route(path: '/{id}/download', name: 'medical_certificate_download', methods: ['GET'])]
    public function download(MedicalCertificate $medicalCertificate, FileUploader $fileUploader): Response
    {
        $uploadedFile = $medicalCertificate->getUploadedFile();
        $path = $fileUploader->getAbsolutePath($uploadedFile);

        return $this->file($path, $uploadedFile->getOriginalFilename(), ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
