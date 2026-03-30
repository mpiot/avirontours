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

use App\Entity\Measure;
use App\Form\MeasureType;
use App\Repository\MeasureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/measure')]
#[IsGranted(new Expression('(is_granted("ROLE_USER") and user.hasValidLicense()) or is_granted("ROLE_ADMIN")'))]
class MeasureController extends AbstractController
{
    #[Route(path: '', name: 'measure_index', methods: ['GET'])]
    public function index(
        MeasureRepository $measureRepository,
        #[MapQueryParameter] int $page = 1,
    ): Response {
        return $this->render('measure/index.html.twig', [
            'measures' => $measureRepository->findPaginated($this->getUser(), $page),
        ]);
    }

    #[Route(path: '/new', name: 'measure_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $measure = new Measure();
        $measure->setUser($this->getUser());
        $form = $this->createForm(MeasureType::class, $measure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($measure);
            $entityManager->flush();

            $this->addFlash('success', 'Votre mesure a été créé avec succès.');

            return $this->redirectToRoute('measure_index');
        }

        return $this->render('measure/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'measure_edit', methods: ['GET', 'POST'])]
    #[IsGranted(new Expression('object.getUser() === user'), 'measure')]
    public function edit(Request $request, EntityManagerInterface $entityManager, Measure $measure): Response
    {
        $form = $this->createForm(MeasureType::class, $measure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Votre mesure a été modifié avec succès.');

            return $this->redirectToRoute('measure_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('measure/edit.html.twig', [
            'form' => $form,
            'measure' => $measure,
        ]);
    }

    #[Route(path: '/{id}', name: 'measure_delete', methods: ['POST'])]
    #[IsGranted(new Expression('object.getUser() === user'), 'measure')]
    public function delete(Request $request, EntityManagerInterface $entityManager, Measure $measure): Response
    {
        if ($this->isCsrfTokenValid('submit', (string) $request->request->get('_token'))) {
            $entityManager->remove($measure);
            $entityManager->flush();

            $this->addFlash('success', 'Votre mesure a été supprimé avec succès.');
        }

        return $this->redirectToRoute('measure_index');
    }
}
