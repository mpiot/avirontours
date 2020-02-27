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

use App\Entity\Invitation;
use App\Entity\Member;
use App\Repository\InvitationRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/invitation")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class InvitationController extends AbstractController
{
    /**
     * @Route("/new/{id}", name="invitation_new", methods={"GET"})
     */
    public function new(Member $member, MailerInterface $mailer, InvitationRepository $invitationRepository, string $senderEmail): Response
    {
        if (null !== $invitationRepository->findOneBy(['member' => $member])) {
            $this->addFlash('danger', 'Une invitation existe dejà pour cet utilisateur.');

            return $this->redirectToRoute('member_show', [
                'id' => $member->getId(),
            ]);
        }

        if (null !== $member->getUser()) {
            $this->addFlash('danger', 'Ce membre est déjà lié à un utilisateur.');

            return $this->redirectToRoute('member_show', [
                'id' => $member->getId(),
            ]);
        }

        $invitation = new Invitation($member);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($invitation);
        $entityManager->flush();

        if (null !== $member->getEmail()) {
            $email = (new TemplatedEmail())
                ->from($senderEmail)
                ->to($member->getEmail())
                ->subject('Invitation')
                ->htmlTemplate('emails/invitation.html.twig')
                ->textTemplate('emails/invitation.txt.twig')
                ->context([
                    'member' => $member,
                    'invitation' => $invitation,
                ])
            ;

            $mailer->send($email);
        }

        return $this->redirectToRoute('member_show', [
            'id' => $member->getId(),
        ]);
    }
}
