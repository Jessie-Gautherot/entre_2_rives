<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactFormType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'autocomplete' => 'name',
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez renseigner votre nom.'),
                    new Length(
                        min: 2,
                        max: 100,
                        minMessage: 'Votre nom doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Votre nom ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'attr' => [
                    'autocomplete' => 'email',
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez renseigner votre adresse e-mail.'),
                    new Email(message: 'Veuillez renseigner une adresse e-mail valide.'),
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Objet',
                'constraints' => [
                    new NotBlank(message: 'Veuillez renseigner un objet.'),
                    new Length(
                        max: 150,
                        maxMessage: 'L’objet ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'attr' => [
                    'rows' => 7,
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez écrire votre message.'),
                    new Length(
                        min: 10,
                        max: 2000,
                        minMessage: 'Votre message doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Votre message ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ]);
    }
}