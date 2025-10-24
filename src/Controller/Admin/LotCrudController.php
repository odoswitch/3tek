<?php

namespace App\Controller\Admin;

use App\Entity\Lot;
use App\Form\LotImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
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
            TextEditorField::new('description', 'Description')
                ->hideOnIndex()
                ->setTrixEditorConfig([
                    'blockAttributes' => [
                        'default' => ['tagName' => 'p'],
                        'heading1' => ['tagName' => 'h2'],
                        'quote' => ['tagName' => 'blockquote'],
                        'code' => ['tagName' => 'pre'],
                    ],
                    'css' => [
                        'attachment' => 'attachment',
                    ],
                ])
                ->setFormTypeOptions([
                    'attr' => [
                        'style' => 'min-height: 300px;',
                    ],
                ]),
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
