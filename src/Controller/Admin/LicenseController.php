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
use App\Entity\License;
use App\Entity\Season;
use App\Form\LicenseEditType;
use App\Form\LicensePaymentType;
use App\Form\LicenseType;
use App\Repository\LicenseRepository;
use App\Service\FileUploader;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\ClearableErrorsInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route(path: '/admin/season/{seasonId}/license')]
#[IsGranted(new Expression('is_granted("ROLE_SEASON_ADMIN") or is_granted("ROLE_SEASON_PAYMENTS_ADMIN") or is_granted("ROLE_SEASON_MEDICAL_CERTIFICATE_ADMIN")'))]
class LicenseController extends AbstractController
{
    #[Route(path: '/new', name: 'license_new', methods: ['GET', 'POST'])]
    public function new(
        #[MapEntity(mapping: ['seasonId' => 'id'])] Season $season,
        Request $request,
        ManagerRegistry $managerRegistry,
        FileUploader $fileUploader
    ): Response {
        $license = new License();
        $form = $this->createForm(LicenseType::class, $license, ['season' => $season]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('medicalCertificate')->get('file')->getData();
            $uploadedFile = $fileUploader->upload($uploadedFile, FileUploader::PRIVATE);
            $license->getMedicalCertificate()->setUploadedFile($uploadedFile);

            $entityManager = $managerRegistry->getManager();
            $entityManager->persist($license);
            $entityManager->flush();

            $this->addFlash('success', 'La licence a été créée avec succès.');

            return $this->redirectToRoute('season_show', [
                'id' => $season->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/license/new.html.twig', [
            'form' => $form,
            'season' => $season,
        ]);
    }

    #[Route(path: '/{id<\d+>}/edit', name: 'license_edit', methods: ['GET', 'POST'])]
    public function edit(
        License $license,
        Request $request,
        ManagerRegistry $managerRegistry,
        FileUploader $fileUploader
    ): Response {
        $form = $this->createForm(LicenseEditType::class, $license, ['season' => $license->getSeasonCategory()->getSeason()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->has('medicalCertificate') && null !== $uploadedFile = $form->get('medicalCertificate')->get('file')->getData()) {
                $uploadedFile = $fileUploader->upload($uploadedFile, FileUploader::PRIVATE);
                $license->getMedicalCertificate()->setUploadedFile($uploadedFile);
            }

            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'La licence a été modifiée avec succès.');

            return $this->redirectToRoute('season_show', [
                'id' => $license->getSeasonCategory()->getSeason()->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/license/edit.html.twig', [
            'form' => $form,
            'license' => $license,
        ]);
    }

    #[Route(path: '/{id<\d+>}/validate-payment', name: 'license_validate_payment', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SEASON_PAYMENTS_ADMIN')]
    public function validatePayment(
        Request $request,
        ManagerRegistry $managerRegistry,
        WorkflowInterface $licenseWorkflow,
        License $license,
    ): Response {
        if (false === $licenseWorkflow->can($license, 'validate_payment')) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(LicensePaymentType::class, $license);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $license->setPayedAt(new \DateTimeImmutable());
            $licenseWorkflow->apply($license, 'validate_payment', [
                'time' => date('y-m-d H:i:s'),
                'user' => $this->getUser()->getFullname(),
            ]);
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'La licence a été modifiée avec succès.');

            return $this->redirectToRoute('season_show', [
                'id' => $license->getSeasonCategory()->getSeason()->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        if ($request->isXmlHttpRequest() && $form instanceof ClearableErrorsInterface) {
            $form->clearErrors(true);
        }

        return $this->render('admin/license/validate_payment.html.twig', [
            'form' => $form,
            'license' => $license,
        ]);
    }

    #[Route(path: '/chain-medical-certificate-validation', name: 'license_validate_medical_certificate', methods: ['GET'])]
    #[IsGranted('ROLE_SEASON_MEDICAL_CERTIFICATE_ADMIN')]
    public function chainValidation(
        #[MapEntity(mapping: ['seasonId' => 'id'])] Season $season,
        LicenseRepository $repository
    ): Response {
        $license = $repository->findOneForValidation($season);
        if (null !== $license) {
            $previousLicenses = $repository->findUserLicences($license->getUser(), (int) (new \DateTime('-3 years'))->format('Y'), $season->getName() - 1);
        }

        return $this->render('admin/license/chain_validation.html.twig', [
            'season' => $season,
            'license' => $license,
            'previous_licences' => $previousLicenses ?? null,
        ]);
    }

    #[Route(
        path: '/{id<\d+>}/medical-certificate/{transitionName<validate|reject|unreject>}',
        name: 'license_medical_certificate_action',
        methods: ['GET']
    )]
    #[IsGranted('ROLE_SEASON_MEDICAL_CERTIFICATE_ADMIN')]
    public function medicalCertificateAction(
        License $license,
        string $transitionName,
        Request $request,
        WorkflowInterface $licenseWorkflow,
        ManagerRegistry $managerRegistry
    ): Response {
        if ($this->isCsrfTokenValid('license-medical-certificate-action', (string) $request->query->get('_token'))) {
            try {
                $licenseWorkflow->apply($license, $transitionName.'_medical_certificate', [
                    'time' => date('y-m-d H:i:s'),
                    'user' => $this->getUser()->getFullname(),
                ]);
                $managerRegistry->getManager()->flush();

                $this->addFlash('success', 'La licence a été modifiée avec succès.');
            } catch (NotEnabledTransitionException $error) {
                throw $this->createAccessDeniedException();
            }
        }

        return new RedirectResponse($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }

    #[Route(path: '/{id<\d+>}/validate', name: 'license_validate', methods: ['GET'])]
    #[IsGranted('ROLE_SEASON_ADMIN')]
    public function validate(
        License $license,
        Request $request,
        WorkflowInterface $licenseWorkflow,
        ManagerRegistry $managerRegistry
    ): Response {
        if ($this->isCsrfTokenValid('license-validate', (string) $request->query->get('_token'))) {
            try {
                $licenseWorkflow->apply($license, 'validate_license', [
                    'time' => date('y-m-d H:i:s'),
                    'user' => $this->getUser()->getFullname(),
                ]);
                $managerRegistry->getManager()->flush();

                $this->addFlash('success', 'La licence a été modifiée avec succès.');
            } catch (NotEnabledTransitionException $error) {
                throw $this->createAccessDeniedException();
            }
        }

        return new RedirectResponse($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }

    #[Route(path: '/{id<\d+>}', name: 'license_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SEASON_ADMIN')]
    public function delete(Request $request, ManagerRegistry $managerRegistry, License $license): Response
    {
        if ($this->isCsrfTokenValid('delete'.$license->getId(), (string) $request->request->get('_token'))) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->remove($license);
            $entityManager->flush();

            $this->addFlash('success', 'La licence a été supprimée avec succès.');
        }

        return $this->redirectToRoute('season_show', ['id' => $license->getSeasonCategory()->getSeason()->getId()]);
    }
}
