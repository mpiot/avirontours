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

use App\Entity\SeasonUser;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Form\RenewType;
use App\Repository\SeasonRepository;
use App\Repository\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/profile")
 */
class ProfileController extends AbstractController
{
    /**
     * @Route("/", name="profile_show", methods="GET")
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function show(UserRepository $userRepository): Response
    {
        $user = $userRepository->findUserProfile($this->getUser());

        return $this->render('profile/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/edit", name="profile_edit", methods="GET|POST")
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function edit(Request $request)
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            $this->addFlash('success', 'Your profile have been successfully edited.');

            return $this->redirectToRoute('profile_show');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit-password", name="profile_edit_password", methods="GET|POST")
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function editPassword(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            $this->addFlash('success', 'Your password have been successfully changed.');

            return $this->redirectToRoute('profile_show');
        }

        return $this->render('profile/edit_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/renew", name="renew")
     * @Security("is_granted('RENEW', user)", statusCode=404)
     */
    public function renew(Request $request, SeasonRepository $seasonRepository): Response
    {
        $season = $seasonRepository->findLastSeason();
        $seasonUser = new SeasonUser($season, $this->getUser());
        $form = $this->createForm(RenewType::class, $seasonUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($seasonUser);
            $entityManager->flush();

            // do anything else you need here, like send an email
            $this->addFlash('success', 'Votre réinscription a bien été prise en compte.');

            return $this->redirectToRoute('profile_show');
        }

        return $this->render('profile/renew.html.twig', [
            'form' => $form->createView(),
            'season' => $season,
        ]);
    }
}
