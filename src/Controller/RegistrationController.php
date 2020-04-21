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

use App\Entity\User;
use App\Form\Model\RegistrationModel;
use App\Form\RegistrationFormType;
use App\Repository\SeasonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register/{licenseType}", requirements={"licenseType"= "annual|indoor"}, name="app_register")
     */
    public function register(string $licenseType, Request $request, UserPasswordEncoderInterface $passwordEncoder, SeasonRepository $seasonRepository): Response
    {
        $season = $seasonRepository->findOneBy([], ['name' => 'DESC']);
        if (null === $season) {
            $this->addFlash('error', 'Il n\'y a pas de saison active.');
            $this->redirectToRoute('app_register');
        }

        $registrationModel = new RegistrationModel();
        $form = $this->createForm(RegistrationFormType::class, $registrationModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $registrationModel->generateUser($licenseType, $season, $passwordEncoder);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email
            $this->addFlash('success', 'Votre inscription a bien été prise en compte, votre compte sera accessible après réglement de votre cotisation.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
