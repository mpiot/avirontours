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
use App\Entity\Group;
use App\Form\GroupType;
use App\Repository\GroupRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/admin/group')]
#[IsGranted('ROLE_USER_ADMIN')]
class GroupController extends AbstractController
{
    #[Route(path: '', name: 'group_index', methods: ['GET'])]
    public function index(GroupRepository $groupRepository): Response
    {
        return $this->render('admin/group/index.html.twig', [
            'groups' => $groupRepository->findBy([], ['name' => 'asc']),
        ]);
    }

    #[Route(path: '/new', name: 'group_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ManagerRegistry $managerRegistry): Response
    {
        $group = new Group();
        $form = $this->createForm(GroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->persist($group);
            $entityManager->flush();

            $this->addFlash('success', 'Le groupe a été créé avec succès.');

            return $this->redirectToRoute('group_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/group/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/{id}', name: 'group_show', methods: ['GET'])]
    public function show(Group $group): Response
    {
        return $this->render('admin/group/show.html.twig', [
            'group' => $group,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'group_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ManagerRegistry $managerRegistry, Group $group): Response
    {
        $form = $this->createForm(GroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'Le groupe a été modifié avec succès.');

            return $this->redirectToRoute('group_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/group/edit.html.twig', [
            'form' => $form,
            'group' => $group,
        ]);
    }

    #[Route(path: '/{id}', name: 'group_delete', methods: ['POST'])]
    public function delete(Request $request, ManagerRegistry $managerRegistry, Group $group): Response
    {
        if ($this->isCsrfTokenValid('delete'.$group->getId(), (string) $request->request->get('_token'))) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->remove($group);
            $entityManager->flush();

            $this->addFlash('success', 'Le groupe a été supprimé avec succès.');
        }

        return $this->redirectToRoute('group_index');
    }
}
