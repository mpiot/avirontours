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

namespace App\Form;

use App\Entity\Invitation;
use App\Entity\User;
use App\Form\DataTransformer\CodeToInvitationTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    private $transformer;
    /* @var Invitation $invitation */
    private $invitation;

    public function __construct(CodeToInvitationTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email')
            ->add('plainPassword', RepeatedType::class, [
                'mapped' => false,
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Mot de passe',
                    // instead of being set onto the object directly,
                    // this is read and encoded in the controller
                    'constraints' => [
                        new NotBlank(),
                        new Length([
                            'min' => 6,
                            // max length allowed by Symfony for security reasons
                            'max' => 4096,
                        ]),
                    ],
                ],
                'second_options' => ['label' => 'Répéter le mot de passe'],
                'invalid_message' => 'Les mots de passes doivent être identiques',
            ])
            ->add('invitation', TextType::class, [
                'label' => 'Invitation',
                'invalid_message' => 'Cette invitation n\'existe pas.',
                'mapped' => false,
            ])
        ;

        $builder->get('invitation')
            ->addModelTransformer($this->transformer);

        $builder->get('invitation')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $data = $event->getForm()->getData();

            if (null !== $data) {
                $this->invitation = $data;
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();

            if ($this->invitation) {
                $data->setMember($this->invitation->getMember());
            }

            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
