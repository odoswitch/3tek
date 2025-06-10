<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [

            FormField::addTab('Information client'),

            TextField::new('office', 'Votre Entreprise'),
            EmailField::new('email', 'Adresse Mail associé'),

            TextField::new('lastname', 'Prénom'),
            TextField::new('name', 'Nom'),
            FormField::addTab('Adresse Client'),
            TextField::new('address', 'Adresse Postale'),
            TextField::new('code', 'Code Postale'),
            TextField::new('ville', 'Votre ville'),
            FormField::addTab('Type Compte'),
            AssociationField::new('lot', 'Type Client')->autocomplete(),
            AssociationField::new('categorie', 'Centre d\'interert')->autocomplete(),

        ];
    }
}
