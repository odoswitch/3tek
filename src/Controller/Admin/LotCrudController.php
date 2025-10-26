<?php

namespace App\Controller\Admin;

use App\Entity\Lot;
use App\Form\LotImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Vich\UploaderBundle\Form\Type\VichImageType;

class LotCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Lot::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Nom du lot'),
            TextareaField::new('description', 'Description')
                ->hideOnIndex()
                ->setFormTypeOptions([
                    'attr' => [
                        'rows' => 8,
                        'placeholder' => 'Description du lot (texte simple, sans HTML)',
                        'class' => 'form-control'
                    ],
                ])
                ->setHelp('Description en texte simple. Évitez les caractères spéciaux qui pourraient causer des problèmes d\'affichage.'),
            NumberField::new('prix', 'Prix (€)'),
            NumberField::new('quantite', 'Quantité en stock')
                ->setHelp('Nombre d\'unités disponibles. Le lot ne sera plus visible quand la quantité atteint 0.'),
            AssociationField::new('cat', 'Catégorie')->autocomplete(),
            AssociationField::new('types', 'Types'),

            CollectionField::new('images', 'Images du lot')
                ->setEntryType(LotImageType::class)
                ->setFormTypeOptions([
                    'by_reference' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'prototype' => true,
                    'label' => 'Images du lot',
                    'entry_options' => [
                        'label' => false,
                    ],
                    'attr' => [
                        'data-add-label' => 'Ajouter une image',
                        'data-delete-label' => 'Supprimer',
                    ],
                ])
                ->onlyOnForms()
                ->setHelp('Ajoutez plusieurs images. Modifiez la position pour changer l\'ordre (0 = image principale). Vous pouvez aussi réorganiser en changeant les numéros de position.')
                ->setCustomOption('allowAdd', true)
                ->setCustomOption('allowDelete', true),
        ];
    }
}
