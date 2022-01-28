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

use App\Entity\Anatomy;
use App\Entity\PhysicalQualities;
use App\Entity\Physiology;
use App\Entity\WorkoutMaximumLoad;
use App\Form\AnatomyType;
use App\Form\PhysicalQualitiesType;
use App\Form\PhysiologyType;
use App\Form\SportProfileConfirurationType;
use App\Form\WorkoutMaximumLoadType;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/sport-profile')]
#[Security('is_granted("ROLE_USER")')]
class SportProfileController extends AbstractController
{
    #[Route(path: '/physiology', name: 'sport_profile_physiology', methods: ['GET', 'POST'])]
    public function physiology(Request $request, ManagerRegistry $managerRegistry): Response
    {
        $physiology = $this->getUser()->getPhysiology() ?? new Physiology($this->getUser());
        $form = $this->createForm(PhysiologyType::class, $physiology);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'Ma physiologie a été modifiée avec succès.');

            return $this->redirectToRoute('sport_profile_physiology', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('sport_profile/physiology.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/anatomy', name: 'sport_profile_anatomy', methods: ['GET', 'POST'])]
    public function anatomy(Request $request, ManagerRegistry $managerRegistry): Response
    {
        $anatomy = $this->getUser()->getAnatomy() ?? new Anatomy($this->getUser());
        $form = $this->createForm(AnatomyType::class, $anatomy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'Mon anatomie a été modifiée avec succès.');

            return $this->redirectToRoute('sport_profile_anatomy', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('sport_profile/anatomy.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/physical-qualities', name: 'sport_profile_physical_qualities', methods: ['GET', 'POST'])]
    public function physicalQualities(Request $request, ManagerRegistry $managerRegistry): Response
    {
        $physicalQualities = $this->getUser()->getPhysicalQualities() ?? new PhysicalQualities($this->getUser());
        $form = $this->createForm(PhysicalQualitiesType::class, $physicalQualities);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'Mes qualités physiques ont été modifiées avec succès.');

            return $this->redirectToRoute('sport_profile_physical_qualities', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('sport_profile/physical_qualities.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/workout-maximum-load', name: 'sport_profile_workout_maximum_load', methods: ['GET', 'POST'])]
    public function workoutMaximumLoad(Request $request, ManagerRegistry $managerRegistry): Response
    {
        $workoutMaximumLoad = $this->getUser()->getWorkoutMaximumLoad() ?? new WorkoutMaximumLoad($this->getUser());
        $form = $this->createForm(WorkoutMaximumLoadType::class, $workoutMaximumLoad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'Mes 1RM on été modifiées avec succès.');

            return $this->redirectToRoute('sport_profile_workout_maximum_load', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('sport_profile/workout_maximum_load.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/configuration', name: 'sport_profile_configuration', methods: ['GET', 'POST'])]
    public function configuration(Request $request, ManagerRegistry $managerRegistry): Response
    {
        $form = $this->createForm(SportProfileConfirurationType::class, $this->getUser());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'La configuration du profil sportif a été modifiée avec succès.');

            return $this->redirectToRoute('sport_profile_configuration', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('sport_profile/configuration.html.twig', [
            'form' => $form,
        ]);
    }
}
