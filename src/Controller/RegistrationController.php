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

namespace App\Controller;

use App\Entity\License;
use App\Entity\SeasonCategory;
use App\Form\Model\RegistrationModel;
use App\Form\RegistrationFormType;
use App\Form\RenewType;
use App\Repository\SeasonCategoryRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register/{slug}", name="app_register")
     * @Entity("seasonCategory", expr="repository.findSubscriptionSeasonCategory(slug)")
     */
    public function register(SeasonCategory $seasonCategory, Request $request, UserPasswordEncoderInterface $passwordEncoder, MailerInterface $mailer): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('profile_show');
        }

        $registrationModel = new RegistrationModel();
        $form = $this->createForm(RegistrationFormType::class, $registrationModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $registrationModel->generateUser($seasonCategory, $passwordEncoder);

            // Persist user
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // Send email
            $email = new Email();
            $email->to($user->getEmail())
                ->text('')
                ->setHeaders(
                    $email->getHeaders()
                        ->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply')
                        ->addTextHeader('X-MJ-TemplateID', '1669922')
                        ->addTextHeader('X-MJ-TemplateLanguage', '1')
                        ->addTextHeader('X-MJ-Vars', json_encode([
                            'fullName' => $user->getFullName(),
                            'username' => $user->getUsername(),
                        ]))
                )
            ;
            $mailer->send($email);

            // Add flash message
            $this->addFlash('success', 'Votre inscription a bien été prise en compte, votre compte sera accessible après réglement de votre cotisation.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/renew/{slug}", name="renew")
     * @Security("is_granted('ROLE_USER')")
     */
    public function renew(string $slug, SeasonCategoryRepository $repository, Request $request, MailerInterface $mailer): Response
    {
        $seasonCategory = $repository->findSubscriptionSeasonCategory($slug, $this->getUser());
        if (null === $seasonCategory) {
            throw $this->createNotFoundException();
        }

        $license = new License($seasonCategory, $this->getUser());
        $form = $this->createForm(RenewType::class, $license);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Persist user
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($license);
            $entityManager->flush();

            // Send email
            $email = new Email();
            $email->to($this->getUser()->getEmail())
                ->text('')
                ->setHeaders(
                    $email->getHeaders()
                        ->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply')
                        ->addTextHeader('X-MJ-TemplateID', '1669929')
                        ->addTextHeader('X-MJ-TemplateLanguage', '1')
                        ->addTextHeader('X-MJ-Vars', json_encode([
                            'fullName' => $this->getUser()->getFullName(),
                        ]))
                )
            ;
            $mailer->send($email);

            // Add flash message
            $this->addFlash('success', 'Votre réinscription a bien été prise en compte.');

            return $this->redirectToRoute('profile_show');
        }

        return $this->render('registration/renew.html.twig', [
            'form' => $form->createView(),
            'season_category' => $seasonCategory,
        ]);
    }
}
