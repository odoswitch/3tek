<?php

namespace App\Controller\Admin;

use App\Entity\Lot;
use Doctrine\DBAL\Types\FloatType;
use Symfony\Component\DomCrawler\Image;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
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
            
            TextField::new('name'),
            TextEditorField::new('description'),
            ImageField::new('image')->setBasePath('public/uploads/images/')->setUploadDir('public/uploads/images/'),
            //->setUploadedFileNamePattern('[year]/[month]/[day].[extension]'),
            NumberField::new('prix'),
            AssociationField::new('cat')->autocomplete(),
            AssociationField::new('types'),
        ];
    }
}
