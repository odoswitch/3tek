<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom est obligatoire']),
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire']),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'L\'email est obligatoire']),
                    new Email(['message' => 'L\'email n\'est pas valide']),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Le téléphone est obligatoire']),
                ],
            ])
            ->add('office', TextType::class, [
                'label' => 'Entreprise',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'L\'entreprise est obligatoire']),
                ],
            ])
            ->add('profileImageFile', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'accept' => 'image/*'],
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, GIF, WEBP)',
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
