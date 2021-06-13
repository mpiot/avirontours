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

use App\Entity\Training;
use App\Form\TrainingType;
use App\Repository\TrainingRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/training')]
#[Security('(is_granted("ROLE_USER") and user.hasValidLicense()) or is_granted("ROLE_ADMIN")')]
class TrainingController extends AbstractController
{
    #[Route(path: '', name: 'training_index', methods: ['GET'])]
    public function index(TrainingRepository $trainingRepository): Response
    {
        return $this->render('training/index.html.twig', [
            'trainings' => $trainingRepository->findUserPaginated($this->getUser()),
        ]);
    }

    #[Route(path: '/new', name: 'training_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $training = new Training($this->getUser());

        return $this->handleForm(
            $this->createForm(TrainingType::class, $training),
            $request,
            function () use ($training) {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($training);
                $entityManager->flush();

                $this->addFlash('success', 'Votre entraînement a été créé avec succès.');

                return $this->redirectToRoute('training_show', [
                    'id' => $training->getId(),
                ], Response::HTTP_SEE_OTHER);
            },
            function (FormInterface $form) {
                return $this->render('training/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        );
    }

    #[Route(path: '/{id}', name: 'training_show', methods: ['GET'])]
    #[Security('training.getUser() == user')]
    public function show(Training $training): Response
    {
        return $this->render('training/show.html.twig', [
            'training' => $training,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'training_edit', methods: ['GET', 'POST'])]
    #[Security('training.getUser() == user')]
    public function edit(Request $request, Training $training): Response
    {
        return $this->handleForm(
            $this->createForm(TrainingType::class, $training),
            $request,
            function () {
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'Votre entraînement a été modifié avec succès.');

                return $this->redirectToRoute('training_index', [], Response::HTTP_SEE_OTHER);
            },
            function (FormInterface $form) use ($training) {
                return $this->render('training/edit.html.twig', [
                    'training' => $training,
                    'form' => $form->createView(),
                ]);
            }
        );
    }

    #[Route(path: '/{id}', name: 'training_delete', methods: ['POST'])]
    #[Security('training.getUser() == user')]
    public function delete(Request $request, Training $training): Response
    {
        if ($this->isCsrfTokenValid('delete'.$training->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($training);
            $entityManager->flush();

            $this->addFlash('success', 'Votre entraînement a été supprimé avec succès.');
        }

        return $this->redirectToRoute('training_index');
    }
}
