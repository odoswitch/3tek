<?php

namespace App\Form;

use App\Entity\LotImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class LotImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('imageFile', VichImageType::class, [
                'required' => false,
                'allow_delete' => true,
                'download_uri' => true,
                'download_label' => 'Télécharger',
                'image_uri' => true,
                'label' => 'Image',
                'attr' => [
                    'placeholder' => 'Choisir une image'
                ],
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'style' => 'width: 80px;',
                ],
                'help' => '0 = image principale. Laissez vide pour ordre automatique.',
                'empty_data' => '0',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LotImage::class,
        ]);
    }
}
