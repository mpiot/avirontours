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

use App\Entity\Physiology;
use App\Entity\User;
use App\Form\PhysiologyType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/user/{id}")
 * @Security("is_granted('ROLE_SPORT_ADMIN')")
 */
class SportsProfileController extends AbstractController
{
    /**
     * @Route("/physiology", name="user_physiology", methods={"GET","POST"})
     */
    public function physiology(Request $request, User $user): Response
    {
        $physiology = $user->getPhysiology() ?? new Physiology($user);
        $form = $this->createForm(PhysiologyType::class, $physiology);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('admin/sports_profile/physiology.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
