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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/shell')]
#[Security('is_granted("ROLE_MATERIAL_ADMIN")')]
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
    public function new(Request $request): Response
    {
        $shell = new Shell();

        return $this->handleForm(
            $this->createForm(ShellType::class, $shell),
            $request,
            function () use ($shell) {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($shell);
                $entityManager->flush();

                $this->addFlash('success', 'Le bâteau a été créé avec succès.');

                return $this->redirectToRoute('shell_index', [], Response::HTTP_SEE_OTHER);
            },
            function (FormInterface $form) {
                return $this->render('admin/shell/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        );
    }

    #[Route(path: '/{id}', name: 'shell_show', methods: ['GET'])]
    public function show(Shell $shell): Response
    {
        return $this->render('admin/shell/show.html.twig', [
            'shell' => $shell,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'shell_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Shell $shell): Response
    {
        return $this->handleForm(
            $this->createForm(ShellEditType::class, $shell),
            $request,
            function () {
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'Le bâteau a été modifié avec succès.');

                return $this->redirectToRoute('shell_index', [], Response::HTTP_SEE_OTHER);
            },
            function (FormInterface $form) use ($shell) {
                return $this->render('admin/shell/edit.html.twig', [
                    'shell' => $shell,
                    'form' => $form->createView(),
                ]);
            }
        );
    }

    #[Route(path: '/{id}', name: 'shell_delete', methods: ['DELETE'])]
    public function delete(Request $request, Shell $shell): Response
    {
        if ($this->isCsrfTokenValid('delete'.$shell->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($shell);
            $entityManager->flush();

            $this->addFlash('success', 'Le bâteau a été supprimé avec succès.');
        }

        return $this->redirectToRoute('shell_index');
    }
}
