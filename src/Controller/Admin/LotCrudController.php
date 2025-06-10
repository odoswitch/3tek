<?php

namespace App\Controller\Admin;

use App\Entity\Lot;
use Doctrine\DBAL\Types\FloatType;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

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
            NumberField::new('prix'),
            AssociationField::new('cat')->autocomplete(),
            AssociationField::new('types'),
        ];
    }
}
