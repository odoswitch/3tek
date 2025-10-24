<?php

namespace App\Controller\Admin;

use App\Entity\EmailLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;

class EmailLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EmailLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Log Email')
            ->setEntityLabelInPlural('Logs Emails')
            ->setPageTitle('index', 'Historique des emails envoyés')
            ->setPageTitle('detail', 'Détails du log email')
            ->setDefaultSort(['sentAt' => 'DESC'])
            ->setPaginatorPageSize(50)
            ->setSearchFields(['recipient', 'subject', 'status', 'emailType'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        // Action personnalisée pour supprimer les anciens logs
        $deleteOldLogs = Action::new('deleteOldLogs', 'Supprimer logs > 30 jours', 'fa fa-trash-alt')
            ->linkToCrudAction('deleteOldLogs')
            ->addCssClass('btn btn-warning')
            ->createAsGlobalAction();
        
        return $actions
            // Désactiver les actions de création et édition
            ->disable(Action::NEW, Action::EDIT)
            // Garder la consultation et la suppression (DELETE est déjà activé par défaut)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            // Ajouter l'action personnalisée
            ->add(Crud::PAGE_INDEX, $deleteOldLogs);
    }
    
    public function deleteOldLogs(AdminUrlGenerator $adminUrlGenerator, EntityManagerInterface $entityManager)
    {
        // Supprimer les logs de plus de 30 jours
        $date = new \DateTime('-30 days');
        
        $qb = $entityManager->createQueryBuilder();
        $qb->delete(EmailLog::class, 'e')
            ->where('e.sentAt < :date')
            ->setParameter('date', $date);
        
        $deletedCount = $qb->getQuery()->execute();
        
        $this->addFlash('success', sprintf('%d log(s) email supprimé(s) (plus de 30 jours)', $deletedCount));
        
        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
        
        return $this->redirect($url);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices([
                'Succès' => 'success',
                'Erreur' => 'error',
            ]))
            ->add(ChoiceFilter::new('emailType', 'Type')->setChoices([
                'Notification nouveau lot' => 'notification_nouveau_lot',
                'Confirmation inscription' => 'confirmation_inscription',
                'Réinitialisation mot de passe' => 'reset_password',
                'Confirmation commande' => 'confirmation_commande',
                'Notification admin' => 'notification_admin',
            ]))
            ->add(DateTimeFilter::new('sentAt', 'Date d\'envoi'));
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            
            DateTimeField::new('sentAt', 'Date/Heure')
                ->setFormat('dd/MM/yyyy HH:mm:ss')
                ->setSortable(true),
            
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'Succès' => 'success',
                    'Erreur' => 'error',
                ])
                ->renderAsBadges([
                    'success' => 'success',
                    'error' => 'danger',
                ])
                ->setSortable(true),
            
            TextField::new('recipient', 'Destinataire')
                ->setSortable(true),
            
            TextField::new('subject', 'Sujet')
                ->setMaxLength(50)
                ->hideOnIndex(),
            
            ChoiceField::new('emailType', 'Type d\'email')
                ->setChoices([
                    'Notification nouveau lot' => 'notification_nouveau_lot',
                    'Confirmation inscription' => 'confirmation_inscription',
                    'Réinitialisation mot de passe' => 'reset_password',
                    'Confirmation commande' => 'confirmation_commande',
                    'Notification admin' => 'notification_admin',
                ])
                ->renderAsBadges([
                    'notification_nouveau_lot' => 'info',
                    'confirmation_inscription' => 'primary',
                    'reset_password' => 'warning',
                    'confirmation_commande' => 'success',
                    'notification_admin' => 'secondary',
                ]),
            
            TextareaField::new('errorMessage', 'Message d\'erreur')
                ->onlyOnDetail()
                ->setHelp('Détails de l\'erreur si l\'envoi a échoué'),
            
            TextareaField::new('context', 'Contexte')
                ->onlyOnDetail()
                ->setHelp('Informations supplémentaires sur l\'email'),
        ];
    }
}
