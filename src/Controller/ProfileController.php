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

use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Repository\SeasonRepository;
use App\Repository\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/profile')]
#[Security('is_granted("ROLE_USER")')]
class ProfileController extends AbstractController
{
    #[Route(path: '', name: 'profile_show', methods: ['GET'])]
    public function show(UserRepository $userRepository, SeasonRepository $seasonRepository): Response
    {
        return $this->render('profile/show.html.twig', [
            'user' => $userRepository->findUserProfile($this->getUser()),
            'renew_season' => $seasonRepository->findRenewSeason($this->getUser()),
        ]);
    }

    #[Route(path: '/edit', name: 'profile_edit', methods: ['GET|POST'])]
    public function edit(Request $request)
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été modifié avec succès.');

            return $this->redirectToRoute('profile_show', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('profile/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/edit-password', name: 'profile_edit_password', methods: ['GET|POST'])]
    public function editPassword(Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');

            return $this->redirectToRoute('profile_show', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('profile/edit_password.html.twig', [
            'form' => $form,
        ]);
    }
}
