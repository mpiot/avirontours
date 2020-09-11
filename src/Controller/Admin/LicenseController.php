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

use App\Entity\License;
use App\Entity\Season;
use App\Form\LicenseEditType;
use App\Form\LicenseType;
use App\Repository\LicenseRepository;
use DoctrineExtensions\Query\Mysql\Date;
use ProxyManager\Exception\ExceptionInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @Route("/admin/season/{season_id}/license")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class LicenseController extends AbstractController
{
    /**
     * @Route("/new", name="license_new", methods={"GET","POST"})
     * @Entity("season", expr="repository.find(season_id)")
     */
    public function new(Request $request, Season $season): Response
    {
        $license = new License();
        $form = $this->createForm(LicenseType::class, $license, ['season' => $season]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($license);
            $entityManager->flush();

            return $this->redirectToRoute('season_show', ['id' => $season->getId()]);
        }

        return $this->render('admin/license/new.html.twig', [
            'season' => $season,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="license_edit", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function edit(Request $request, License $license): Response
    {
        $form = $this->createForm(LicenseEditType::class, $license, ['season' => $license->getSeasonCategory()->getSeason()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('season_show', [
                'id' => $license->getSeasonCategory()->getSeason()->getId(),
            ]);
        }

        return $this->render('admin/license/edit.html.twig', [
            'license' => $license,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="license_delete", methods={"DELETE"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function delete(Request $request, License $license): Response
    {
        if ($this->isCsrfTokenValid('delete'.$license->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($license);
            $entityManager->flush();
        }

        return $this->redirectToRoute('season_show', ['id' => $license->getSeasonCategory()->getSeason()->getId()]);
    }

    /**
     * @Route("/{id}/apply-transition", name="license_apply_transition", methods={"POST"})
     */
    public function applyTransition(Request $request, WorkflowInterface $licenseWorkflow, License $license)
    {
        try {
            $licenseWorkflow
                ->apply($license, $request->request->get('transition'), [
                    'time' => date('y-m-d H:i:s'),
                ]);
            $this->getDoctrine()->getManager()->flush();
        } catch (ExceptionInterface $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        if ($request->request->has('redirectUrl')) {
            return $this->redirect($request->request->get('redirectUrl'));
        }

        return $this->redirectToRoute('season_show', [
            'id' => $license->getSeasonCategory()->getSeason()->getId(),
        ]);
    }

    /**
     * @Route("/chain-medical-certificate-validation", name="license_validate_medical_certificate", methods={"GET"})
     * @Entity("season", expr="repository.find(season_id)")
     * @Entity("license", expr="repository.findOneForValidation(season)")
     */
    public function chainValidation(Season $season, LicenseRepository $repository): Response
    {
        $license = $repository->findOneForValidation($season);

        if (null !== $license) {
            $previousLicenses = $repository->findUserLicences($license->getUser(), (new \DateTime('-3 years'))->format('Y'), ($season->getName() - 1));
        }

        return $this->render('admin/license/chain_validation.html.twig', [
            'season' => $season,
            'license' => $license,
            'previous_licences' => $previousLicenses ?? null,
        ]);
    }
}
