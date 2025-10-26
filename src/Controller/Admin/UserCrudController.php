<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addTab('Information client'),

            IdField::new('id')->onlyOnIndex(),
            TextField::new('office', 'Entreprise'),
            EmailField::new('email', 'Email'),
            TextField::new('lastname', 'Prénom'),
            TextField::new('name', 'Nom'),
            TextField::new('phone', 'Téléphone'),

            // Champ mot de passe (uniquement en création et édition)
            TextField::new('plainPassword', 'Nouveau mot de passe')
                ->setFormType(PasswordType::class)
                ->setRequired(false)
                ->onlyOnForms()
                ->setHelp('Laissez vide pour conserver le mot de passe actuel'),

            // Champ rôles avec choix multiples
            ChoiceField::new('roles', 'Rôles')
                ->setChoices([
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                ])
                ->allowMultipleChoices()
                ->renderExpanded()
                ->setHelp('Sélectionnez les rôles de l\'utilisateur'),

            BooleanField::new('isVerified', 'Email vérifié'),

            FormField::addTab('Adresse Client'),
            TextField::new('address', 'Adresse')->hideOnIndex(),
            TextField::new('code', 'Code Postal')->hideOnIndex(),
            TextField::new('ville', 'Ville')->hideOnIndex(),

            FormField::addTab('Type Compte'),
            AssociationField::new('type', 'Type Client')
                ->autocomplete()
                ->setHelp('Type de client (Grossiste, Détaillant, etc.)'),
            AssociationField::new('categorie', 'Catégories')
                ->autocomplete()
                ->setHelp('Catégories de produits accessibles'),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            // Hasher le mot de passe si fourni
            if ($entityInstance->getPlainPassword()) {
                $hashedPassword = $this->passwordHasher->hashPassword(
                    $entityInstance,
                    $entityInstance->getPlainPassword()
                );
                $entityInstance->setPassword($hashedPassword);
            }

            // S'assurer que ROLE_USER est toujours présent
            $roles = $entityInstance->getRoles();
            if (!in_array('ROLE_USER', $roles)) {
                $roles[] = 'ROLE_USER';
                $entityInstance->setRoles($roles);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            // Hasher le mot de passe si un nouveau est fourni
            if ($entityInstance->getPlainPassword()) {
                $hashedPassword = $this->passwordHasher->hashPassword(
                    $entityInstance,
                    $entityInstance->getPlainPassword()
                );
                $entityInstance->setPassword($hashedPassword);
            }

            // S'assurer que ROLE_USER est toujours présent
            $roles = $entityInstance->getRoles();
            if (!in_array('ROLE_USER', $roles)) {
                $roles[] = 'ROLE_USER';
                $entityInstance->setRoles($roles);
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            // Vérifier que l'utilisateur n'est pas l'admin actuel
            $currentUser = $this->getUser();
            if ($currentUser && $currentUser->getId() === $entityInstance->getId()) {
                throw new \Exception('Vous ne pouvez pas supprimer votre propre compte administrateur.');
            }

            // Vérifier que l'utilisateur n'est pas le dernier admin
            if (in_array('ROLE_ADMIN', $entityInstance->getRoles())) {
                $adminCount = $entityManager->getRepository(User::class)
                    ->createQueryBuilder('u')
                    ->select('COUNT(u.id)')
                    ->where('u.roles LIKE :role')
                    ->setParameter('role', '%ROLE_ADMIN%')
                    ->getQuery()
                    ->getSingleScalarResult();

                if ($adminCount <= 1) {
                    throw new \Exception('Impossible de supprimer le dernier administrateur du système.');
                }
            }

            // Supprimer les relations avant de supprimer l'utilisateur
            $this->cleanupUserRelations($entityManager, $entityInstance);
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }

    /**
     * Nettoie les relations de l'utilisateur avant suppression
     */
    private function cleanupUserRelations(EntityManagerInterface $entityManager, User $user): void
    {
        // Annuler toutes les commandes en attente
        $commandes = $user->getCommandes();
        foreach ($commandes as $commande) {
            if ($commande->getStatut() === 'en_attente' || $commande->getStatut() === 'reserve') {
                $commande->setStatut('annulee');
                $entityManager->persist($commande);

                // Libérer le lot si il était réservé
                $lot = $commande->getLot();
                if ($lot && $lot->getStatut() === 'reserve' && $lot->getReservePar() === $user) {
                    $lot->setStatut('disponible');
                    $lot->setReservePar(null);
                    $lot->setReserveAt(null);
                    $entityManager->persist($lot);
                }
            }
        }

        // Supprimer les entrées de file d'attente
        $fileAttenteRepository = $entityManager->getRepository(\App\Entity\FileAttente::class);
        $fileAttenteEntries = $fileAttenteRepository->findBy(['user' => $user]);
        foreach ($fileAttenteEntries as $entry) {
            $entityManager->remove($entry);
        }

        // Supprimer le panier et les favoris
        foreach ($user->getPaniers() as $panier) {
            $entityManager->remove($panier);
        }

        foreach ($user->getFavoris() as $favori) {
            $entityManager->remove($favori);
        }

        $entityManager->flush();
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setPageTitle('index', 'Gestion des utilisateurs')
            ->setPageTitle('new', 'Créer un utilisateur')
            ->setPageTitle('edit', 'Modifier l\'utilisateur')
            ->setPageTitle('detail', 'Détails de l\'utilisateur')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }
}
