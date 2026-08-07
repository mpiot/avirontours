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

use App\Attestation\AttestationRefusal;
use App\Attestation\AttestationValidity;
use App\Entity\License;
use App\Entity\MedicalCertificate;
use App\Enum\CertificateLevel;
use App\Enum\CertificateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class MedicalCertificateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', EnumType::class, [
                'label' => 'Type de certificat',
                'class' => CertificateType::class,
                'choice_label' => 'label',
                'expanded' => true,
            ])
            ->add('level', EnumType::class, [
                'label' => 'Niveau',
                'class' => CertificateLevel::class,
                'choice_label' => 'label',
                'expanded' => true,
                'help' => 'Compétition pour participer aux régates, y compris la Coupe de Noël. Loisir sinon.',
            ])
            ->add('date', DateType::class, [
                'label' => 'Date de signature du document',
                'help' => 'Telle qu\'elle figure sur le document.',
                'widget' => 'single_text',
            ])
            ->add('file', FileType::class, [
                'label' => 'Fichier',
                'help' => 'PDF ou image (JPEG, PNG), 3 Mo maximum.',
                'constraints' => [
                    new NotNull(),
                    new File(
                        maxSize: '3M',
                        mimeTypes: ['application/pdf', 'application/x-pdf', 'image/jpeg', 'image/png'],
                        mimeTypesMessage: 'Le fichier doit être au format PDF ou bien une image (JPEG, PNG).',
                    ),
                ],
                'mapped' => false,
            ])
        ;

        // Should the form resolve the attestation validity
        if (true === $options['resolve_attestation']) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
            $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->define('resolve_attestation')
            ->required()
            ->allowedTypes('bool')
        ;

        $resolver->setDefaults([
            'data_class' => MedicalCertificate::class,
            'resolve_attestation' => false,
        ]);
    }

    public function onPreSetData(PreSetDataEvent $event): void
    {
        $license = $event->getForm()->getParent()?->getData();
        if (!$license instanceof License) {
            return;
        }

        $form = $event->getForm();
        $certificate = $event->getData();

        $this->addTypeAndLevelFields(
            $form,
            $license,
            $certificate instanceof MedicalCertificate ? $certificate->getType() : null
        );
    }

    public function onPreSubmit(PreSubmitEvent $event): void
    {
        $license = $event->getForm()->getParent()?->getData();
        if (!$license instanceof License) {
            return;
        }

        $form = $event->getForm();
        $data = $event->getData();
        $type = \is_array($data) ? $data['type'] ?? null : null;

        $this->addTypeAndLevelFields(
            $form,
            $license,
            \is_string($type) ? CertificateType::tryFrom($type) : null
        );
    }

    private function addTypeAndLevelFields(FormInterface $form, License $license, ?CertificateType $type): void
    {
        $validity = AttestationValidity::forLicense($license);

        $this->addTypeField($form, $validity, null !== $license->getUser()?->getBirthday());
        $this->addLevelField($form, CertificateType::Attestation === $type ? $validity->getRequiredLevel() : null);
    }

    private function addTypeField(FormInterface $form, AttestationValidity $validity, bool $birthdayKnown): void
    {
        $form->add('type', EnumType::class, [
            'label' => 'Type de certificat',
            'class' => CertificateType::class,
            'choice_label' => 'label',
            'expanded' => true,
            'help' => $birthdayKnown
                ? $this->buildTypeHelp($validity)
                : 'Renseignez votre date de naissance: elle détermine si l\'attestation QS-Sport suffit.',
            'choice_attr' => static fn (CertificateType $choice): array => CertificateType::Attestation === $choice && false === $validity->isAvailable()
                ? ['disabled' => 'disabled']
                : [],
        ]);
    }

    private function addLevelField(FormInterface $form, ?CertificateLevel $certificateLevel): void
    {
        $form->add('level', EnumType::class, [
            'label' => 'Niveau',
            'class' => CertificateLevel::class,
            'choice_label' => 'label',
            'expanded' => true,
            'help' => null === $certificateLevel
                ? 'Compétition pour participer aux régates, y compris la Coupe de Noël. Loisir sinon.'
                : 'Repris du certificat médical que votre attestation prolonge. Pour en changer, fournissez un certificat médical.',
            'choice_attr' => static fn (CertificateLevel $choice): array => null !== $certificateLevel && $choice !== $certificateLevel
                ? ['disabled' => 'disabled']
                : [],
        ]);
    }

    private function buildTypeHelp(AttestationValidity $validity): string
    {
        if (null !== $validity->refusal) {
            return match ($validity->refusal) {
                AttestationRefusal::FirstLicense => 'L\'attestation QS-Sport ne vaut qu\'en renouvellement: une première licence demande un certificat médical.',
                AttestationRefusal::MajorityReached => 'Vous n\'avez pas encore fourni de certificat médical en tant que majeur: il en faut un cette année, l\'attestation QS-Sport ne suffit plus.',
                AttestationRefusal::CompetitionCertificateExpired => \sprintf(
                    'Votre dernier certificat médical de niveau Compétition expire le %s, avant la transmission de votre licence: il en faut un nouveau cette année.',
                    $validity->certificateExpiryDate->format('d/m/Y'),
                ),
                AttestationRefusal::EnrolmentGap => \sprintf(
                    'Votre inscription au club a été interrompue (saison %d manquante): l\'attestation QS-Sport ne peut plus prolonger votre dernier certificat médical.',
                    $validity->missingSeasonYear,
                ),
            };
        }

        $level = $validity->getRequiredLevel();
        if (null === $level) {
            return 'L\'attestation QS-Sport est acceptée pour les personnes mineures.';
        }

        return \sprintf(
            'L\'attestation QS-Sport prolonge votre dernier certificat médical, dont elle conserve le niveau: %s.',
            $level->label(),
        );
    }
}
