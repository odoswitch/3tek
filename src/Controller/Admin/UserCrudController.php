<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
       /* return [
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
            TextField::new('password'),
            AssociationField::new('categories')->autocomplete(),
            //ChoiceField::new('categories')->allowMultipleChoices(),
            
            BooleanField::new('isVerified'),
        ];*/
        yield TextField::new('office');
        yield TextField::new('email');
        yield TextField::new('name');
        yield TextField::new('prenom');
        yield TextField::new('phone');
        yield TextField::new('address');
        yield TextField::new('code');
        yield TextField::new('ville');
        yield TextField::new('pays');
        yield AssociationField::new('categories')->autocomplete();
        //yield AssociationField::new('categories')->autocomplete();
        yield BooleanField::new('isVerified');
    }
        
        
        
    
}
