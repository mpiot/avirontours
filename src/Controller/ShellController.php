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

namespace App\Controller;

use App\Entity\Shell;
use App\Form\ShellEditType;
use App\Form\ShellType;
use App\Repository\ShellRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/shell")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class ShellController extends AbstractController
{
    /**
     * @Route("/", name="shell_index", methods={"GET"})
     */
    public function index(ShellRepository $shellRepository): Response
    {
        return $this->render('shell/index.html.twig', [
            'shells' => $shellRepository->findAllNameOrdered(),
        ]);
    }

    /**
     * @Route("/new", name="shell_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $shell = new Shell();
        $form = $this->createForm(ShellType::class, $shell);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($shell);
            $entityManager->flush();

            return $this->redirectToRoute('shell_index');
        }

        return $this->render('shell/new.html.twig', [
            'shell' => $shell,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="shell_show", methods={"GET"})
     */
    public function show(Shell $shell): Response
    {
        return $this->render('shell/show.html.twig', [
            'shell' => $shell,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="shell_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Shell $shell): Response
    {
        $form = $this->createForm(ShellEditType::class, $shell);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('shell_index');
        }

        return $this->render('shell/edit.html.twig', [
            'shell' => $shell,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="shell_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Shell $shell): Response
    {
        if ($this->isCsrfTokenValid('delete'.$shell->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($shell);
            $entityManager->flush();
        }

        return $this->redirectToRoute('shell_index');
    }
}
