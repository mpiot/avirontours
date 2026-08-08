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

namespace App\Controller;

use App\Entity\License;
use App\Repository\LicenseRepository;
use App\Service\PdfGenerator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

use function Symfony\Component\String\u;

#[Route(path: '/payment-attestation')]
#[IsGranted('PUBLIC_ACCESS')]
class PaymentAttestationController extends AbstractController
{
    #[Route(path: '/download/{id}', name: 'payment_attestation_download', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function download(
        License $license,
        PdfGenerator $pdfGenerator,
    ): Response {
        if ($this->getUser() !== $license->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Without a payment there is nothing to attest: the document would state a nil amount and,
        // `payedAt` being null, today's date as the day it was settled.
        if ($license->getPayments()->isEmpty()) {
            throw $this->createNotFoundException();
        }

        $filesystem = new Filesystem();
        $pdfFilename = $filesystem->tempnam(sys_get_temp_dir(), 'payment_attestation_', '.pdf');
        $pdfGenerator->twigToPdf(
            'payment_attestation/pdf.html.twig',
            ['license' => $license],
            $pdfFilename
        );

        $fileName = u('Attestation de paiement')
            ->append(' - ', (string) $license->getSeasonCategory()->getSeason()->getName(), '.pdf')
        ;

        return $this
            ->file($pdfFilename, $fileName->toString())
            ->deleteFileAfterSend()
        ;
    }

    #[Route(path: '/check/{uuid}', name: 'payment_attestation_check', methods: ['GET'])]
    public function check(string $uuid, LicenseRepository $licenseRepository): Response
    {
        $license = null;
        if (Uuid::isValid($uuid)) {
            $license = $licenseRepository->findOneBy(['uuid' => $uuid]);
        }

        // Same reason as `download()`: an unpaid licence has no attestation, so it has none to verify.
        if (null !== $license && $license->getPayments()->isEmpty()) {
            $license = null;
        }

        return $this->render('payment_attestation/check.html.twig', [
            'license' => $license,
        ]);
    }
}
