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

namespace App\Form;

use App\Entity\LogbookEntry;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogbookEntryFinishType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('recaptcha', Recaptcha3Type::class, [
                'action_name' => 'logbook_finish',
                'mapped' => false,
                'constraints' => [
                    new Recaptcha3(),
                ],
            ])
            ->remove('shell')
            ->remove('crewMembers')
            ->remove('nonUserCrewMembers')
            ->remove('startAt')
        ;

        $builder->get('endAt')->setRequired(true);
        $builder->get('coveredDistance')->setRequired(true);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            /** @var LogbookEntry $data */
            $data = $event->getData();

            $data->setEndAt(new \DateTime());

            $event->setData($data);
        });
    }

    public function getParent(): string
    {
        return LogbookEntryType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['finish'],
        ]);
    }
}
