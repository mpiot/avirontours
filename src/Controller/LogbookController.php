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

use App\Repository\MemberRepository;
use App\Repository\ShellRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class LogbookController extends AbstractController
{
    /**
     * @Route("/logbook", name="logbook_homepage")
     * @Security("is_granted('ROLE_USER')")
     */
    public function homepage(ShellRepository $shellRepository, MemberRepository $memberRepository)
    {
        return $this->render('logbook/homepage.html.twig', [
            'topDistances' => $memberRepository->findTop10Distances(),
            'topSessions' => $memberRepository->findTop10Sessions(),
            'topShells' => $shellRepository->findTop10Sessions(),
        ]);
    }
}
