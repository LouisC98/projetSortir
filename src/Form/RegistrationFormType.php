<?php
// src/Form/RegistrationFormType.php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{EmailType, PasswordType, TextType, TelType};
use Symfony\Component\Validator\Constraints\{NotBlank, Length, Email};

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'constraints' => [
                    new NotBlank(message: "Choisissez un pseudo"),
                    new Length(min: 3, max: 50),
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 50),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 50),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                    new Length(max: 180),
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 10),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,  // ce champ n’est pas mappé directement sur l’entité
                'label' => 'Mot de passe',
                'constraints' => [
                    new NotBlank(message: "Entrez un mot de passe"),
                    new Length(min: 6, max: 4096),
                ],
            ])
        ;
    }
}
