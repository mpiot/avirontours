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
use App\Entity\ShellDamage;
use App\Form\ShellDamageType;
use App\Repository\ShellDamageRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/admin/shell-damage')]
#[IsGranted('ROLE_MATERIAL_ADMIN')]
class ShellDamageController extends AbstractController
{
    #[Route(path: '', name: 'shell_damage_index', methods: ['GET'])]
    public function index(Request $request, ShellDamageRepository $shellDamageRepository): Response
    {
        return $this->render('admin/shell_damage/index.html.twig', [
            'shell_damages' => $shellDamageRepository->findAllPaginated($request->query->getInt('page', 1)),
        ]);
    }

    #[Route(path: '/new', name: 'shell_damage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ManagerRegistry $managerRegistry): Response
    {
        $shellDamage = new ShellDamage();
        $form = $this->createForm(ShellDamageType::class, $shellDamage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->persist($shellDamage);
            $entityManager->flush();

            $this->addFlash('success', 'L\'avarie  a été créée avec succès.');

            return $this->redirectToRoute('shell_damage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/shell_damage/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'shell_damage_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ManagerRegistry $managerRegistry, ShellDamage $shellDamage): Response
    {
        $form = $this->createForm(ShellDamageType::class, $shellDamage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'L\'avarie  a été modifiée avec succès.');

            return $this->redirectToRoute('shell_damage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/shell_damage/edit.html.twig', [
            'form' => $form,
            'shell_damage' => $shellDamage,
        ]);
    }

    #[Route(path: '/{id}', name: 'shell_damage_delete', methods: ['POST'])]
    public function delete(Request $request, ManagerRegistry $managerRegistry, ShellDamage $shellDamage): Response
    {
        if ($this->isCsrfTokenValid('delete'.$shellDamage->getId(), (string) $request->request->get('_token'))) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->remove($shellDamage);
            $entityManager->flush();

            $this->addFlash('success', 'L\'avarie  a été supprimée avec succès.');
        }

        return $this->redirectToRoute('shell_damage_index');
    }
}
