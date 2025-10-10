<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EditProfilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'attr' => ['class' => 'w-full border border-gray-300 rounded px-3 py-2'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'w-full border border-gray-300 rounded px-3 py-2'],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => ['class' => 'w-full border border-gray-300 rounded px-3 py-2'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'attr' => ['class' => 'w-full border border-gray-300 rounded px-3 py-2'],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'class' => 'w-full border border-gray-300 rounded px-3 py-2',
                    'pattern' => '[0-9]{10}',
                    'maxlength' => 10,
                    'oninput' => "this.value=this.value.replace(/[^0-9]/g,'');"
                ],
            ])
            // Champs pour changement de mot de passe (non mappés à la DB)
            ->add('oldPassword', PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Ancien mot de passe',
                'attr' => ['class' => 'w-full border border-gray-300 rounded px-3 py-2'],
            ])
            ->add('newPassword', PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Nouveau mot de passe',
                'attr' => ['class' => 'w-full border border-gray-300 rounded px-3 py-2'],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Confirmer le nouveau mot de passe',
                'attr' => ['class' => 'w-full border border-gray-300 rounded px-3 py-2'],
            ])
            ->add('photoFile', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false, // important : non lié directement à l’entité
                'required' => false,
                'attr' => [
                    'class' => 'block w-full text-sm text-gray-700 border border-gray-300 rounded-lg p-2 bg-white cursor-pointer hover:bg-gray-50 transition-all duration-150',
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '4M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG ou WebP)',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
