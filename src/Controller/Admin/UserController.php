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
use App\Entity\User;
use App\Form\UserEditType;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\ClearableErrorsInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/admin/user')]
#[IsGranted('ROLE_USER_ADMIN')]
class UserController extends AbstractController
{
    #[Route(path: '', name: 'user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findPaginated(
                $request->query->get('q'),
                $request->query->getInt('page', 1)
            ),
        ]);
    }

    #[Route(path: '/new', name: 'user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ManagerRegistry $managerRegistry): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a été créé avec succès.');

            return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($request->isXmlHttpRequest() && $form instanceof ClearableErrorsInterface) {
            $form->clearErrors(true);
        }

        return $this->render('admin/user/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/{id}', name: 'user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ManagerRegistry $managerRegistry, User $user): Response
    {
        $form = $this->createForm(UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'L\'utilisateur a été modifié avec succès.');

            return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($request->isXmlHttpRequest() && $form instanceof ClearableErrorsInterface) {
            $form->clearErrors(true);
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route(path: '/{id}', name: 'user_delete', methods: ['POST'])]
    public function delete(Request $request, ManagerRegistry $managerRegistry, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), (string) $request->request->get('_token'))) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a été supprimé avec succès.');
        }

        return $this->redirectToRoute('user_index');
    }
}
