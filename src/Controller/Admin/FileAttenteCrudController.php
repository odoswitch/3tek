<?php

namespace App\Controller\Admin;

use App\Entity\FileAttente;
use App\Service\LotLiberationServiceAmeliore;
use Doctrine\ORM\EntityManagerInterface;
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
    private LotLiberationServiceAmeliore $lotLiberationService;

    public function __construct(LotLiberationServiceAmeliore $lotLiberationService)
    {
        $this->lotLiberationService = $lotLiberationService;
    }

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
                    'En attente validation' => 'en_attente_validation',
                    'Notifié' => 'notifie',
                    'Délai dépassé' => 'delai_depasse',
                ])
                ->renderAsBadges([
                    'en_attente' => 'warning',
                    'en_attente_validation' => 'info',
                    'notifie' => 'success',
                    'delai_depasse' => 'danger',
                ]),
            DateTimeField::new('createdAt', 'Date d\'ajout')->setFormat('dd/MM/yyyy HH:mm'),
            DateTimeField::new('notifiedAt', 'Date de notification')->setFormat('dd/MM/yyyy HH:mm')->hideOnIndex(),
            DateTimeField::new('expiresAt', 'Expire le')->setFormat('dd/MM/yyyy HH:mm')->hideOnIndex(),
            DateTimeField::new('expiredAt', 'Expiré le')->setFormat('dd/MM/yyyy HH:mm')->hideOnIndex(),
        ];
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof FileAttente) {
            $lot = $entityInstance->getLot();

            // Log de débogage
            error_log("DEBUG DELETE FILE ATTENTE: Suppression file d'attente ID=" . $entityInstance->getId() . ", Lot ID=" . $lot->getId());

            // Toujours libérer le lot quand on supprime une file d'attente
            // Le service LotLiberationServiceAmeliore gère la logique :
            // - Si d'autres utilisateurs en file d'attente : réserve pour le suivant
            // - Si plus personne en file d'attente : libère pour tous
            error_log("DEBUG DELETE FILE ATTENTE: Libération automatique du lot en cours...");
            $this->lotLiberationService->libererLot($lot);
            error_log("DEBUG DELETE FILE ATTENTE: Lot libéré automatiquement avec succès");

            // Nettoyer automatiquement les lots orphelins après suppression
            error_log("DEBUG DELETE FILE ATTENTE: Nettoyage automatique des lots orphelins");
            $this->lotLiberationService->nettoyerLotsOrphelins();
            error_log("DEBUG DELETE FILE ATTENTE: Nettoyage terminé");
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
