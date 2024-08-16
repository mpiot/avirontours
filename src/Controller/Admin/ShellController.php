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
use App\Entity\Shell;
use App\Form\ShellEditType;
use App\Form\ShellType;
use App\Repository\ShellRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/admin/shell')]
#[IsGranted('ROLE_MATERIAL_ADMIN')]
class ShellController extends AbstractController
{
    #[Route(path: '', name: 'shell_index', methods: ['GET'])]
    public function index(ShellRepository $shellRepository): Response
    {
        return $this->render('admin/shell/index.html.twig', [
            'shells' => $shellRepository->findAllNameOrdered(),
        ]);
    }

    #[Route(path: '/new', name: 'shell_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ManagerRegistry $managerRegistry): Response
    {
        $shell = new Shell();
        $form = $this->createForm(ShellType::class, $shell);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->persist($shell);
            $entityManager->flush();

            $this->addFlash('success', 'Le bâteau a été créé avec succès.');

            return $this->redirectToRoute('shell_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/shell/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/{id<\d+>}', name: 'shell_show', methods: ['GET'])]
    public function show(Shell $shell): Response
    {
        return $this->render('admin/shell/show.html.twig', [
            'shell' => $shell,
        ]);
    }

    #[Route(path: '/{id<\d+>}/edit', name: 'shell_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ManagerRegistry $managerRegistry, Shell $shell): Response
    {
        $form = $this->createForm(ShellEditType::class, $shell);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'Le bâteau a été modifié avec succès.');

            return $this->redirectToRoute('shell_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/shell/edit.html.twig', [
            'form' => $form,
            'shell' => $shell,
        ]);
    }

    #[Route(path: '/{id<\d+>}', name: 'shell_delete', methods: ['POST'])]
    public function delete(Request $request, ManagerRegistry $managerRegistry, Shell $shell): Response
    {
        if ($this->isCsrfTokenValid('delete'.$shell->getId(), (string) $request->request->get('_token'))) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->remove($shell);
            $entityManager->flush();

            $this->addFlash('success', 'Le bâteau a été supprimé avec succès.');
        }

        return $this->redirectToRoute('shell_index');
    }
}
