<?php

namespace App\Controller\Admin;

use App\Entity\Lot;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class LotCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Lot::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            //IdField::new('id'),
            TextField::new('name'),
            TextEditorField::new('description'),
            TextField::new('image'),
            AssociationField::new('cat'),
            AssociationField::new('types'),
        ];
    }
}
