<?php

namespace App\Form;

use App\Entity\CommandeLigne;
use App\Entity\Lot;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class CommandeLigneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lot', EntityType::class, [
                'class' => Lot::class,
                'choice_label' => 'name',
                'label' => 'Lot',
                'required' => true,
                'placeholder' => 'Sélectionnez un lot',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1
                ]
            ])
            ->add('prixUnitaire', NumberType::class, [
                'label' => 'Prix unitaire (€)',
                'required' => true,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommandeLigne::class,
        ]);
    }
}

