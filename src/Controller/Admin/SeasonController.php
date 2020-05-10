<?php

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

use App\Entity\Season;
use App\Form\SeasonType;
use App\Repository\LicenseRepository;
use App\Repository\SeasonRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/season")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class SeasonController extends AbstractController
{
    /**
     * @Route("/", name="season_index", methods={"GET"})
     */
    public function index(SeasonRepository $seasonRepository): Response
    {
        return $this->render('admin/season/index.html.twig', [
            'seasons' => $seasonRepository->findBy([], ['name' => 'desc']),
        ]);
    }

    /**
     * @Route("/new", name="season_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $season = new Season();
        $form = $this->createForm(SeasonType::class, $season);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($season);
            $entityManager->flush();

            return $this->redirectToRoute('season_index');
        }

        return $this->render('admin/season/new.html.twig', [
            'season' => $season,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="season_show", methods={"GET"})
     */
    public function show(Request $request, LicenseRepository $licenseRepository, Season $season): Response
    {
        return $this->render('admin/season/show.html.twig', [
            'season' => $season,
            'licenses' => $licenseRepository->findBySeasonPaginated(
                $season,
                $request->query->getAlnum('q'),
                $request->query->getInt('page', 1)
            ),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="season_edit", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function edit(Request $request, Season $season): Response
    {
        $form = $this->createForm(SeasonType::class, $season);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('season_index');
        }

        return $this->render('admin/season/edit.html.twig', [
            'season' => $season,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="season_delete", methods={"DELETE"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function delete(Request $request, Season $season): Response
    {
        if ($this->isCsrfTokenValid('delete'.$season->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($season);
            $entityManager->flush();
        }

        return $this->redirectToRoute('season_index');
    }
}
