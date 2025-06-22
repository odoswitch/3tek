<?php

namespace App\Controller\Admin;

use App\Entity\Lot;


use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\VichImageField;
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

            TextField::new('name'),
            TextEditorField::new('description'),
            ImageField::new('image', 'Image actuelle')->setBasePath('public/uploads/images/')->hideOnForm(),

            TextField::new('imageFile')->setFormType(VichImageType::class)->onlyOnForms(),
            //ImageField::new('imageFile')->setBasePath('/uploads/images/')->setUploadDir('public/uploads/images/')->setLabel('Uploader Image')->setRequired(false)->onlyOnForms()->setFormType(VichFileType::class),
            //->setUploadedFileNamePattern('[year]/[month]/[day].[extension]'),
            NumberField::new('prix'),
            AssociationField::new('cat')->autocomplete(),
            AssociationField::new('types'),
        ];
    }
}
