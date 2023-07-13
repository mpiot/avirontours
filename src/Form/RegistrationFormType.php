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

use App\Entity\PostalCode;
use App\Entity\User;
use App\Form\Model\RegistrationModel;
use App\Form\Type\LaneTypeType;
use App\Repository\PostalCodeRepository;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;

class RegistrationFormType extends AbstractType
{
    public function __construct(private readonly PostalCodeRepository $repository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('gender', ChoiceType::class, [
                'label' => 'Genre',
                'choices' => User::getAvailableGenders(),
                'expanded' => true,
                'label_attr' => ['class' => 'radio-custom radio-inline'],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['autocomplete' => 'given-name'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['autocomplete' => 'family-name'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['autocomplete' => 'email'],
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'Numéro de téléphone',
                'attr' => ['autocomplete' => 'tel-national'],
                'required' => false,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passes doivent être identiques.',
                'first_options' => [
                    'label' => 'Mot de passe',
                    'constraints' => [
                        new Length(['min' => 6, 'max' => 4096]),
                        new NotCompromisedPassword(),
                    ],
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Répéter le mot de passe',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
            ])
            ->add('laneNumber', TextType::class, [
                'label' => 'Numéro',
            ])
            ->add('laneType', LaneTypeType::class, [
                'label' => 'Type de voie',
            ])
            ->add('laneName', TextType::class, [
                'label' => 'Nom de voie',
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['autocomplete' => 'postal-code'],
            ])
            ->add('birthCountry', CountryType::class, [
                'label' => 'Pays de naissance',
                'preferred_choices' => ['FR'],
                'placeholder' => '--- Sélectionner un pays ---',
            ])
            ->add('birthday', BirthdayType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'attr' => ['autocomplete' => 'bday'],
            ])
            ->add('firstLegalGuardian', LegalGuardianType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('secondLegalGuardian', LegalGuardianType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('medicalCertificate', RegistrationMedicalCertificateType::class, [
                'label' => false,
            ])
            ->add('federationEmailAllowed', CheckboxType::class, [
                'label' => 'Recevoir les emails de la Fédération Française d\'Aviron',
                'label_attr' => ['class' => 'checkbox-custom'],
                'required' => false,
            ])
            ->add('clubEmailAllowed', CheckboxType::class, [
                'label' => 'Recevoir les emails du club',
                'label_attr' => ['class' => 'checkbox-custom'],
                'required' => false,
            ])
            ->add('optionalInsurance', CheckboxType::class, [
                'label' => 'Souscrire l\'Option I.A. Sport (+11,34 €)',
                'label_attr' => ['class' => 'checkbox-custom'],
                'required' => false,
            ])
            ->add('agreeSwim', CheckboxType::class, [
                'label' => 'J\'atteste savoir nager 25m avec un départ plongé',
                'label_attr' => ['class' => 'checkbox-custom'],
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez savoir nager 25m avec un départ plongé pour vous inscrire.',
                    ]),
                ],
            ])
            ->add('recaptcha', Recaptcha3Type::class, [
                'action_name' => 'register',
                'mapped' => false,
                'constraints' => [
                    new Recaptcha3(),
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $this->formModifier($event->getForm(), $event->getData()->postalCode);
        });

        $builder->get('postalCode')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $this->formModifier($event->getForm()->getParent(), $event->getForm()->getData());
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegistrationModel::class,
            'validation_groups' => ['Default', 'new'],
        ]);
    }

    public function formModifier(FormInterface $form, string $postalCode = null): void
    {
        $cities = null === $postalCode ? [] : $this->repository->findBy(['postalCode' => $postalCode]);
        $cities = array_map(fn (PostalCode $postalCode) => $postalCode->getCity(), $cities);

        $form->add('city', ChoiceType::class, [
            'placeholder' => '',
            'choices' => array_combine($cities, $cities),
        ]);
    }
}
