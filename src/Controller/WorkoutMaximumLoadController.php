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

use App\Entity\WorkoutMaximumLoad;
use App\Form\WorkoutMaximumLoadType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/workout-maximum-load')]
#[Security('(is_granted("ROLE_USER") and user.hasValidLicense()) or is_granted("ROLE_ADMIN")')]
class WorkoutMaximumLoadController extends AbstractController
{
    #[Route(path: '', name: 'workout_maximum_load_show', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('workout_maximum_load/show.html.twig');
    }

    #[Route(path: '/edit', name: 'workout_maximum_load_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        $workoutMaximumLoad = $this->getUser()->getWorkoutMaximumLoad() ?? new WorkoutMaximumLoad($this->getUser());
        $form = $this->createForm(WorkoutMaximumLoadType::class, $workoutMaximumLoad);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Vos 1RM été modifié avec succès.');

            return $this->redirectToRoute('workout_maximum_load_show');
        }

        return $this->render('workout_maximum_load/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
