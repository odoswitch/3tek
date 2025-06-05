<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('office'),
            TextField::new('email'),
            TextField::new('name'),
            TextField::new('prenom'),
            TextField::new('phone'),
            TextField::new('address'),
            TextField::new('address'),
            TextField::new('code'),
            TextField::new('ville'),
            TextField::new('pays'),
            AssociationField::new('categories')->autocomplete(),
            BooleanField::new('isVerified'),
        ];
    }
        
    
}
