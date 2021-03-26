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

use App\Entity\ShellDamageCategory;
use App\Form\ShellDamageCategoryType;
use App\Repository\ShellDamageCategoryRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("is_granted('ROLE_MATERIAL_ADMIN')")
 */
#[Route(path: '/admin/shell-damage-category')]
class ShellDamageCategoryController extends AbstractController
{
    #[Route(path: '', name: 'shell_damage_category_index', methods: ['GET'])]
    public function index(ShellDamageCategoryRepository $shellDamageRepository): Response
    {
        return $this->render('admin/shell_damage_category/index.html.twig', [
            'shell_damage_category_categories' => $shellDamageRepository->findBy([], ['priority' => 'desc', 'name' => 'asc']),
        ]);
    }

    #[Route(path: '/new', name: 'shell_damage_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $shellDamage = new ShellDamageCategory();
        $form = $this->createForm(ShellDamageCategoryType::class, $shellDamage);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($shellDamage);
            $entityManager->flush();

            $this->addFlash('success', 'La catégorie d\'avarie  a été créée avec succès.');

            return $this->redirectToRoute('shell_damage_category_index');
        }

        return $this->render('admin/shell_damage_category/new.html.twig', [
            'shell' => $shellDamage,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'shell_damage_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ShellDamageCategory $shellDamage): Response
    {
        $form = $this->createForm(ShellDamageCategoryType::class, $shellDamage);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'La catégorie d\'avarie  a été modifiée avec succès.');

            return $this->redirectToRoute('shell_damage_category_index');
        }

        return $this->render('admin/shell_damage_category/edit.html.twig', [
            'shell_damage_category' => $shellDamage,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}', name: 'shell_damage_category_delete', methods: ['DELETE'])]
    public function delete(Request $request, ShellDamageCategory $shellDamage): Response
    {
        if ($this->isCsrfTokenValid('delete'.$shellDamage->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($shellDamage);
            $entityManager->flush();

            $this->addFlash('success', 'La catégorie d\'avarie  a été supprimée avec succès.');
        }

        return $this->redirectToRoute('shell_damage_category_index');
    }
}
