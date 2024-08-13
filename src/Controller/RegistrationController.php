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

use App\Entity\SeasonCategory;
use App\Form\Model\Registration;
use App\Form\RegistrationType;
use App\Form\RenewType;
use App\Repository\SeasonCategoryRepository;
use App\Service\FileUploader;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\ClearableErrorsInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RegistrationController extends AbstractController
{
    #[Route(path: '/register/{slug}', name: 'app_register')]
    public function register(
        #[MapEntity(expr: 'repository.findSubscriptionSeasonCategory(slug)')] SeasonCategory $seasonCategory,
        string $publicDir,
        Request $request,
        ManagerRegistry $managerRegistry,
        FileUploader $fileUploader,
        MailerInterface $mailer
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('profile_show');
        }

        $registration = new Registration($seasonCategory);
        $form = $this->createForm(RegistrationType::class, $registration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('license')->get('medicalCertificate')->get('file')->getData();
            $uploadedFile = $fileUploader->upload($uploadedFile, FileUploader::PRIVATE);
            $registration->license->getMedicalCertificate()->setUploadedFile($uploadedFile);
            $registration->license->setUser($registration->user);

            $entityManager = $managerRegistry->getManager();
            $entityManager->persist($registration->user);
            $entityManager->persist($registration->license);
            $entityManager->flush();

            // Send email
            $email = (new TemplatedEmail())
                ->to($registration->user->getEmail())
                ->subject('Inscription à l\'Aviron Tours Métropole')
                ->htmlTemplate('emails/registration.html.twig')
                ->addPart(new DataPart(new File("{$publicDir}/files/droit-image.pdf"), 'Droit à l\'image.pdf'))
                ->addPart(new DataPart(new File("{$publicDir}/files/cerfa-10008-02.pdf"), 'Cerfa - Fiche de liaison sanitaire.pdf'))
                ->context([
                    'fullName' => $registration->user->getFullName(),
                    'userIdentifier' => $registration->user->getUserIdentifier(),
                ])
            ;
            $mailer->send($email);

            return $this->redirectToRoute('app_register_confirmation');
        }

        if ($request->isXmlHttpRequest() && $form instanceof ClearableErrorsInterface) {
            $form->clearErrors(true);
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/register/confirmation', name: 'app_register_confirmation')]
    public function registerConfirmation(): Response
    {
        return $this->render('registration/register_confirmation.html.twig');
    }

    #[Route(path: '/renew/{slug}', name: 'renew')]
    #[IsGranted('ROLE_USER')]
    public function renew(
        string $slug,
        string $publicDir,
        Request $request,
        SeasonCategoryRepository $seasonCategoryRepository,
        ManagerRegistry $managerRegistry,
        FileUploader $fileUploader,
        MailerInterface $mailer
    ): Response {
        $seasonCategory = $seasonCategoryRepository->findSubscriptionSeasonCategory($slug, $this->getUser());
        if (null === $seasonCategory) {
            throw $this->createNotFoundException();
        }

        $registration = new Registration($seasonCategory, $this->getUser());
        $form = $this->createForm(RenewType::class, $registration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('license')->get('medicalCertificate')->get('file')->getData();
            $uploadedFile = $fileUploader->upload($uploadedFile, FileUploader::PRIVATE);
            $registration->license->getMedicalCertificate()->setUploadedFile($uploadedFile);
            $registration->license->setUser($registration->user);

            $entityManager = $managerRegistry->getManager();
            $entityManager->persist($registration->license);
            $entityManager->flush();

            // Send email
            $email = (new TemplatedEmail())
                ->to($registration->user->getEmail())
                ->subject('Réinscription à l\'Aviron Tours Métropole')
                ->htmlTemplate('emails/renew.html.twig')
                ->addPart(new DataPart(new File("{$publicDir}/files/droit-image.pdf"), 'Droit à l\'image.pdf'))
                ->addPart(new DataPart(new File("{$publicDir}/files/cerfa-10008-02.pdf"), 'Cerfa - Fiche de liaison sanitaire.pdf'))
                ->context([
                    'fullName' => $registration->user->getFullName(),
                ])
            ;
            $mailer->send($email);

            return $this->redirectToRoute('app_renew_confirmation');
        }

        if ($request->isXmlHttpRequest() && $form instanceof ClearableErrorsInterface) {
            $form->clearErrors(true);
        }

        return $this->render('registration/renew.html.twig', [
            'form' => $form,
            'season_category' => $seasonCategory,
        ]);
    }

    #[Route(path: '/renew/confirmation', name: 'app_renew_confirmation')]
    public function renewConfirmation(): Response
    {
        return $this->render('registration/renew_confirmation.html.twig');
    }
}
