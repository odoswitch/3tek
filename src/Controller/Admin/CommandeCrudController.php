<?php

namespace App\Controller\Admin;

use App\Entity\Commande;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Doctrine\ORM\EntityManagerInterface;

class CommandeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Commande::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('numeroCommande', 'N° Commande'),
            TextField::new('user.email', 'Client')->onlyOnIndex(),
            TextField::new('user.name', 'Prénom')->onlyOnDetail(),
            TextField::new('user.lastname', 'Nom')->onlyOnDetail(),
            TextField::new('user.phone', 'Téléphone')->onlyOnDetail(),
            TextField::new('user.office', 'Entreprise')->onlyOnDetail(),
            TextField::new('lot.name', 'Lot'),
            NumberField::new('quantite', 'Quantité'),
            NumberField::new('prixUnitaire', 'Prix unitaire')->setNumDecimals(2)->formatValue(function ($value) {
                return number_format($value, 2, ',', ' ') . ' €';
            }),
            NumberField::new('prixTotal', 'Total')->setNumDecimals(2)->formatValue(function ($value) {
                return number_format($value, 2, ',', ' ') . ' €';
            }),
            ChoiceField::new('statut', 'Statut')
                ->setChoices([
                    'En attente de paiement' => 'en_attente',
                    'Validée' => 'validee',
                    'Annulée' => 'annulee',
                ])
                ->renderAsBadges([
                    'en_attente' => 'warning',
                    'validee' => 'success',
                    'annulee' => 'danger',
                ]),
            DateTimeField::new('createdAt', 'Date de commande')->setFormat('dd/MM/yyyy HH:mm'),
            DateTimeField::new('validatedAt', 'Date de validation')->setFormat('dd/MM/yyyy HH:mm')->hideOnIndex(),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Commande) {
            // Si la commande passe à "validée", réduire le stock
            if ($entityInstance->getStatut() === 'validee' && !$entityInstance->getValidatedAt()) {
                $lot = $entityInstance->getLot();
                $nouvelleQuantite = $lot->getQuantite() - $entityInstance->getQuantite();
                $lot->setQuantite(max(0, $nouvelleQuantite));
                $entityInstance->setValidatedAt(new \DateTimeImmutable());
                
                $entityManager->persist($lot);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Commande) {
            // Si la commande passe à "validée", réduire le stock
            if ($entityInstance->getStatut() === 'validee' && !$entityInstance->getValidatedAt()) {
                $lot = $entityInstance->getLot();
                $nouvelleQuantite = $lot->getQuantite() - $entityInstance->getQuantite();
                $lot->setQuantite(max(0, $nouvelleQuantite));
                $entityInstance->setValidatedAt(new \DateTimeImmutable());
                
                $entityManager->persist($lot);
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}
