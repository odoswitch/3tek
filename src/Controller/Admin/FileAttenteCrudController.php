<?php

namespace App\Controller\Admin;

use App\Entity\FileAttente;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class FileAttenteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FileAttente::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('File d\'attente')
            ->setEntityLabelInPlural('Files d\'attente')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            AssociationField::new('lot', 'Lot'),
            AssociationField::new('user', 'Utilisateur'),
            NumberField::new('position', 'Position'),
            ChoiceField::new('statut', 'Statut')
                ->setChoices([
                    'En attente' => 'en_attente',
                    'Notifié' => 'notifie',
                    'Expiré' => 'expire',
                ])
                ->renderAsBadges([
                    'en_attente' => 'warning',
                    'notifie' => 'success',
                    'expire' => 'danger',
                ]),
            DateTimeField::new('createdAt', 'Date d\'ajout')->setFormat('dd/MM/yyyy HH:mm'),
            DateTimeField::new('notifiedAt', 'Date de notification')->setFormat('dd/MM/yyyy HH:mm')->hideOnIndex(),
        ];
    }
}
