<?php

namespace App\Controller\Admin;

use App\Entity\Commande;
use App\Entity\FileAttente;
use App\Entity\Lot;
use App\Entity\User;
use App\Repository\FileAttenteRepository;
use App\Repository\LotRepository;
use App\Repository\UserRepository;
use App\Service\LotLiberationServiceAmeliore;
use App\Service\StockSynchronizationService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CommandeCrudController extends AbstractCrudController
{
    public function __construct(
        private LotLiberationServiceAmeliore $lotLiberationService,
        private StockSynchronizationService $stockSyncService,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private LotRepository $lotRepository,
        private FileAttenteRepository $fileAttenteRepository
    ) {}

    public static function getEntityFqcn(): string
    {
        return Commande::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setPageTitle('index', 'Liste des commandes')
            ->setPageTitle('edit', 'Modifier une commande')
            ->setPageTitle('detail', 'Détails de la commande');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW) // Supprimer le bouton "Créer"
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, Action::new('valider_commande', 'Valider')
                ->linkToCrudAction('validerCommande')
                ->setIcon('fa fa-check')
                ->setCssClass('btn btn-success')
                ->displayIf(function ($entity) {
                    return $entity->getStatut() === 'en_attente';
                }))
            ->add(Crud::PAGE_INDEX, Action::new('annuler_commande', 'Annuler')
                ->linkToCrudAction('annulerCommande')
                ->setIcon('fa fa-times')
                ->setCssClass('btn btn-danger')
                ->displayIf(function ($entity) {
                    return in_array($entity->getStatut(), ['en_attente', 'reserve']);
                }))
            ->add(Crud::PAGE_INDEX, Action::new('reserver_commande', 'Réserver')
                ->linkToCrudAction('reserverCommande')
                ->setIcon('fa fa-lock')
                ->setCssClass('btn btn-warning')
                ->displayIf(function ($entity) {
                    return $entity->getStatut() === 'en_attente';
                }))
            ->add(Crud::PAGE_INDEX, Action::new('liberer_lot', 'Libérer le lot')
                ->linkToCrudAction('libererLot')
                ->setIcon('fa fa-unlock')
                ->setCssClass('btn btn-info')
                ->displayIf(function ($entity) {
                    return $entity->getLot() !== null && $entity->getStatut() === 'reserve';
                }))
            ->add(Crud::PAGE_INDEX, Action::new('nettoyer_lots', 'Nettoyer les lots orphelins')
                ->linkToCrudAction('nettoyerLotsOrphelins')
                ->setIcon('fa fa-broom')
                ->setCssClass('btn btn-secondary')
                ->createAsGlobalAction())
            ->add(Crud::PAGE_INDEX, Action::new('creer_commande_tiers', 'Créer commande pour tiers')
                ->linkToCrudAction('creerCommandeTiers')
                ->setIcon('fa fa-plus')
                ->setCssClass('btn btn-primary')
                ->createAsGlobalAction())
            ->add(Crud::PAGE_INDEX, Action::new('ajouter_ligne', 'Ajouter ligne')
                ->linkToCrudAction('ajouterLigne')
                ->setIcon('fa fa-plus-circle')
                ->setCssClass('btn btn-info')
                ->displayIf(function ($entity) {
                    return $entity->getStatut() === 'en_attente';
                }))
            ->add(Crud::PAGE_INDEX, Action::new('generer_pdf', 'Générer PDF')
                ->linkToCrudAction('genererPdf')
                ->setIcon('fa fa-file-pdf')
                ->setCssClass('btn btn-danger')
                ->displayIf(function ($entity) {
                    return $entity->getStatut() !== 'annulee';
                }))
            ->add(Crud::PAGE_EDIT, Action::new('ajouter_lot_commande', 'Ajouter un lot')
                ->linkToCrudAction('ajouterLotCommande')
                ->setIcon('fa fa-plus')
                ->setCssClass('btn btn-success')
                ->displayIf(function ($entity) {
                    return $entity->getStatut() === 'en_attente';
                }));
    }


    public function libererLot(Commande $commande): Response
    {
        $lot = $commande->getLot();

        if (!$lot) {
            $this->addFlash('danger', 'Cette commande n\'a pas de lot associé.');
            return $this->redirectToRoute('admin');
        }

        error_log("DEBUG LIBERATION MANUELLE: Libération du lot ID=" . $lot->getId());

        $this->lotLiberationService->libererLot($lot);

        $this->addFlash('success', 'Le lot a été libéré avec succès !');

        return $this->redirectToRoute('admin');
    }

    public function validerCommande(Commande $commande): Response
    {
        $lot = $commande->getLot();
        if (!$lot) {
            $this->addFlash('danger', 'Cette commande n\'a pas de lot associé.');
            return $this->redirectToRoute('admin');
        }

        $commande->setStatut('validee');
        $commande->setValidatedAt(new \DateTimeImmutable());

        $lot->setStatut('vendu');
        $lot->setQuantite(0);

        $this->entityManager->persist($commande);
        $this->entityManager->persist($lot);
        $this->entityManager->flush();

        $this->addFlash('success', 'La commande a été validée avec succès !');

        return $this->redirectToRoute('admin');
    }

    public function annulerCommande(Commande $commande): Response
    {
        $lot = $commande->getLot();
        error_log("DEBUG ANNULATION ADMIN: Annulation commande ID=" . $commande->getId());

        $commande->setStatut('annulee');

        if ($lot) {
            $this->lotLiberationService->libererLot($lot);
            error_log("DEBUG ANNULATION ADMIN: Lot ID=" . $lot->getId() . " libéré");
        } else {
            error_log("DEBUG ANNULATION ADMIN: Commande ID=" . $commande->getId() . " n'a pas de lot associé - annulation simple");
        }

        $this->entityManager->persist($commande);
        $this->entityManager->flush();

        $this->lotLiberationService->nettoyerLotsOrphelins(); // Auto-cleanup

        if ($lot) {
            $this->addFlash('success', 'La commande a été annulée et le lot libéré !');
        } else {
            $this->addFlash('success', 'La commande a été annulée !');
        }

        return $this->redirectToRoute('admin');
    }

    public function reserverCommande(Commande $commande): Response
    {
        $lot = $commande->getLot();
        if (!$lot) {
            $this->addFlash('danger', 'Cette commande n\'a pas de lot associé.');
            return $this->redirectToRoute('admin');
        }

        $commande->setStatut('reserve');
        $lot->setStatut('reserve');
        $lot->setReservePar($commande->getUser());
        $lot->setReserveAt(new \DateTimeImmutable());

        $this->entityManager->persist($commande);
        $this->entityManager->persist($lot);
        $this->entityManager->flush();

        $this->addFlash('success', 'La commande a été réservée avec succès !');

        return $this->redirectToRoute('admin');
    }

    public function nettoyerLotsOrphelins(): Response
    {
        error_log("DEBUG NETTOYAGE: Nettoyage des lots orphelins demandé");

        $this->lotLiberationService->nettoyerLotsOrphelins();

        $this->addFlash('success', 'Les lots orphelins ont été nettoyés avec succès !');

        return $this->redirectToRoute('admin', [
            'crudController' => CommandeCrudController::class,
            'crudAction' => 'index'
        ]);
    }

    public function creerCommandeTiers(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $userId = $request->request->get('user_id');
            $statut = $request->request->get('statut', 'en_attente');
            $lots = $request->request->all('lots');

            $user = $this->userRepository->find($userId);

            if (!$user) {
                $this->addFlash('danger', 'Utilisateur introuvable.');
                return $this->redirectToRoute('admin');
            }

            if (empty($lots)) {
                $this->addFlash('danger', 'Aucun lot sélectionné.');
                return $this->redirectToRoute('admin');
            }

            // Créer la commande
            $commande = new Commande();
            $commande->setNumeroCommande('CMD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)));
            $commande->setUser($user);
            $commande->setStatut($statut);
            $commande->setCreatedAt(new \DateTimeImmutable());

            $totalCommande = 0;
            $premierLot = null;

            // Traiter chaque lot
            foreach ($lots as $lotData) {
                $lotId = $lotData['lot_id'] ?? null;
                $quantite = (int) ($lotData['quantite'] ?? 1);
                $prixUnitaire = (float) ($lotData['prix_unitaire'] ?? 0);

                if (!$lotId) continue;

                $lot = $this->lotRepository->find($lotId);
                if (!$lot) continue;

                if ($quantite > $lot->getQuantite()) {
                    $this->addFlash('danger', "Quantité demandée non disponible pour le lot {$lot->getName()}.");
                    return $this->redirectToRoute('admin');
                }

                // Créer la ligne de commande
                $ligne = new \App\Entity\CommandeLigne();
                $ligne->setCommande($commande);
                $ligne->setLot($lot);
                $ligne->setQuantite($quantite);
                $ligne->setPrixUnitaire($prixUnitaire);
                $ligne->setPrixTotal($prixUnitaire * $quantite);

                $this->entityManager->persist($ligne);

                $totalCommande += $prixUnitaire * $quantite;

                // Garder le premier lot pour la compatibilité
                if (!$premierLot) {
                    $premierLot = $lot;
                    $commande->setLot($lot);
                    $commande->setQuantite($quantite);
                    $commande->setPrixUnitaire($prixUnitaire);
                }
            }

            $commande->setPrixTotal($totalCommande);

            $this->entityManager->persist($commande);

            // Gérer le statut et le stock pour chaque lot
            foreach ($lots as $lotData) {
                $lotId = $lotData['lot_id'] ?? null;
                if (!$lotId) continue;

                $lot = $this->lotRepository->find($lotId);
                if (!$lot) continue;

                $quantite = (int) ($lotData['quantite'] ?? 1);
                $nouvelleQuantite = $lot->getQuantite() - $quantite;

                if ($statut === 'validee') {
                    // Commande validée : réduire le stock
                    $lot->setQuantite(max(0, $nouvelleQuantite));

                    if ($lot->getQuantite() == 0) {
                        $lot->setStatut('vendu');
                    } else {
                        // Si il reste du stock, le lot reste disponible
                        $lot->setStatut('disponible');
                    }

                    $this->entityManager->persist($lot);
                } elseif ($statut === 'reserve') {
                    // Commande réservée : réserver le lot
                    $lot->setStatut('reserve');
                    $lot->setReservePar($user);
                    $lot->setReserveAt(new \DateTimeImmutable());
                    $lot->setQuantite(max(0, $nouvelleQuantite)); // Réduire le stock visible

                    $this->entityManager->persist($lot);
                } elseif ($statut === 'en_attente') {
                    // Commande en attente : réduire le stock visible mais garder disponible
                    $lot->setQuantite(max(0, $nouvelleQuantite));

                    // Si stock = 0, passer à réservé
                    if ($lot->getQuantite() == 0) {
                        $lot->setStatut('reserve');
                        $lot->setReservePar($user);
                        $lot->setReserveAt(new \DateTimeImmutable());
                    }

                    $this->entityManager->persist($lot);
                }
            }

            if ($statut === 'validee') {
                $commande->setValidatedAt(new \DateTimeImmutable());
            }

            $this->entityManager->flush();

            $this->addFlash('success', "Commande créée avec succès pour {$user->getEmail()} ! Total: " . number_format($totalCommande, 2, ',', ' ') . " €");

            return $this->redirectToRoute('admin');
        }

        // Afficher le formulaire
        $users = $this->userRepository->findAll();
        $lots = $this->lotRepository->findBy(['statut' => 'disponible']);

        // Formater les données des lots pour le JavaScript
        $lotsFormatted = [];
        foreach ($lots as $lot) {
            $lotsFormatted[] = [
                'id' => $lot->getId(),
                'name' => $lot->getName(),
                'prix' => $lot->getPrix(),
                'quantite' => $lot->getQuantite()
            ];
        }

        return $this->render('admin/creer_commande_tiers.html.twig', [
            'users' => $users,
            'lots' => $lotsFormatted,
        ]);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('numeroCommande', 'N° Commande'),
        ];

        // Champs pour la création/modification
        if ($pageName === Crud::PAGE_NEW || $pageName === Crud::PAGE_EDIT) {
            $fields[] = AssociationField::new('user', 'Client')
                ->setRequired(true)
                ->setHelp('Sélectionnez le client pour cette commande');

            $fields[] = AssociationField::new('lot', 'Lot principal')
                ->setRequired(false)
                ->setHelp('Lot principal de la commande (optionnel si vous utilisez les lignes multiples)');

            $fields[] = NumberField::new('quantite', 'Quantité')
                ->setRequired(false)
                ->setHelp('Quantité pour le lot principal');

            $fields[] = NumberField::new('prixUnitaire', 'Prix unitaire (€)')
                ->setRequired(false)
                ->setNumDecimals(2)
                ->setHelp('Prix unitaire pour le lot principal');

            $fields[] = NumberField::new('prixTotal', 'Prix total (€)')
                ->setRequired(true)
                ->setNumDecimals(2)
                ->setHelp('Total de la commande (calculé automatiquement)')
                ->setFormTypeOption('attr', ['id' => 'prix-total-field', 'readonly' => true]);

            $fields[] = ChoiceField::new('statut', 'Statut')
                ->setChoices([
                    'En attente' => 'en_attente',
                    'Réservé' => 'reserve',
                    'Validé' => 'validee',
                ])
                ->setRequired(true);
        } else {
            // Champs pour l'affichage (index/detail)
            $fields[] = TextField::new('user.email', 'Client')->onlyOnIndex();
            $fields[] = TextField::new('user.name', 'Prénom')->onlyOnDetail();
            $fields[] = TextField::new('user.lastname', 'Nom')->onlyOnDetail();
            $fields[] = TextField::new('user.phone', 'Téléphone')->onlyOnDetail();
            $fields[] = TextField::new('user.office', 'Entreprise')->onlyOnDetail();

            // Afficher les lignes de commande dans la vue détail
            if ($pageName === Crud::PAGE_DETAIL) {
                $fields[] = TextField::new('lignes', 'Lignes de commande')
                    ->formatValue(function ($value, $entity) {
                        if (!$entity->getLignes() || $entity->getLignes()->isEmpty()) {
                            return 'Aucune ligne';
                        }

                        $html = '<div class="table-responsive"><table class="table table-sm">';
                        $html .= '<thead><tr><th>Lot</th><th>Quantité</th><th>Prix unitaire</th><th>Total</th></tr></thead><tbody>';

                        foreach ($entity->getLignes() as $ligne) {
                            $html .= sprintf(
                                '<tr><td>%s</td><td>%d</td><td>%s €</td><td>%s €</td></tr>',
                                $ligne->getLot()->getName(),
                                $ligne->getQuantite(),
                                number_format($ligne->getPrixUnitaire(), 2, ',', ' '),
                                number_format($ligne->getPrixTotal(), 2, ',', ' ')
                            );
                        }

                        $html .= '</tbody></table></div>';
                        return $html;
                    })
                    ->onlyOnDetail();
            }

            $fields[] = TextField::new('lot.name', 'Lot principal')->onlyOnIndex();
            $fields[] = NumberField::new('quantite', 'Quantité')->onlyOnIndex();
            $fields[] = NumberField::new('prixUnitaire', 'Prix unitaire')->setNumDecimals(2)->formatValue(function ($value) {
                return number_format($value, 2, ',', ' ') . ' €';
            })->onlyOnIndex();
            $fields[] = NumberField::new('prixTotal', 'Total')->setNumDecimals(2)->formatValue(function ($value) {
                return number_format($value, 2, ',', ' ') . ' €';
            });
        }

        $fields[] = ChoiceField::new('statut', 'Statut')
            ->setChoices([
                'En attente' => 'en_attente',
                'Réservé' => 'reserve',
                'Validé' => 'validee',
                'Annulé' => 'annulee',
            ])
            ->renderAsBadges([
                'en_attente' => 'warning',
                'reserve' => 'info',
                'validee' => 'success',
                'annulee' => 'danger',
            ])
            ->onlyOnIndex();

        $fields[] = DateTimeField::new('createdAt', 'Date de commande')->setFormat('dd/MM/yyyy HH:mm');
        $fields[] = DateTimeField::new('validatedAt', 'Date de validation')->setFormat('dd/MM/yyyy HH:mm')->hideOnIndex();

        return $fields;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Commande) {
            // Générer le numéro de commande s'il n'existe pas
            if (!$entityInstance->getNumeroCommande()) {
                $entityInstance->setNumeroCommande('CMD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)));
            }

            // Calculer automatiquement le prix total à partir des lignes
            $totalLignes = $entityInstance->getTotalLignes();
            $entityInstance->setPrixTotal($totalLignes);

            // Si pas de lignes mais qu'il y a un lot principal, calculer avec les anciens champs
            if ($totalLignes == 0 && $entityInstance->getLot() && $entityInstance->getPrixUnitaire() && $entityInstance->getQuantite()) {
                $prixTotal = $entityInstance->getPrixUnitaire() * $entityInstance->getQuantite();
                $entityInstance->setPrixTotal($prixTotal);
            }

            // Définir la date de création
            if (!$entityInstance->getCreatedAt()) {
                $entityInstance->setCreatedAt(new \DateTimeImmutable());
            }

            // Si la commande passe à "validée", réduire le stock
            if ($entityInstance->getStatut() === 'validee' && !$entityInstance->getValidatedAt()) {
                $lot = $entityInstance->getLot();

                // Vérifier si la commande a un lot associé
                if ($lot) {
                    $nouvelleQuantite = $lot->getQuantite() - $entityInstance->getQuantite();
                    $lot->setQuantite(max(0, $nouvelleQuantite));
                    $entityInstance->setValidatedAt(new \DateTimeImmutable());

                    $entityManager->persist($lot);
                }
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        error_log("DEBUG UPDATE: Méthode updateEntity appelée pour commande ID=" . $entityInstance->getId());

        if ($entityInstance instanceof Commande) {
            // Calculer automatiquement le prix total à partir des lignes
            $totalLignes = $entityInstance->getTotalLignes();
            $entityInstance->setPrixTotal($totalLignes);

            // Si pas de lignes mais qu'il y a un lot principal, calculer avec les anciens champs
            if ($totalLignes == 0 && $entityInstance->getLot() && $entityInstance->getPrixUnitaire() && $entityInstance->getQuantite()) {
                $prixTotal = $entityInstance->getPrixUnitaire() * $entityInstance->getQuantite();
                $entityInstance->setPrixTotal($prixTotal);
            }

            $lot = $entityInstance->getLot();

            // Vérifier si la commande a un lot associé
            if (!$lot) {
                error_log("DEBUG UPDATE: Commande ID=" . $entityInstance->getId() . " n'a pas de lot associé - pas de traitement spécial");
                parent::updateEntity($entityManager, $entityInstance);
                return;
            }

            $ancienStatut = $entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance)['statut'] ?? null;

            error_log("DEBUG UPDATE: Commande ID=" . $entityInstance->getId() . ", Ancien statut=" . ($ancienStatut ?: 'NULL') . ", Nouveau statut=" . $entityInstance->getStatut());

            // Si la commande passe à "validée" (paiement confirmé)
            if ($entityInstance->getStatut() === 'validee' && $ancienStatut !== 'validee') {
                error_log("DEBUG UPDATE: Commande ID=" . $entityInstance->getId() . " passe à validée");

                // Réduire le stock
                $nouvelleQuantite = $lot->getQuantite() - $entityInstance->getQuantite();
                $lot->setQuantite(max(0, $nouvelleQuantite));

                // Si plus de stock, marquer comme vendu
                if ($lot->getQuantite() == 0) {
                    $lot->setStatut('vendu');
                }

                $entityInstance->setValidatedAt(new \DateTimeImmutable());

                $entityManager->persist($lot);
                error_log("DEBUG UPDATE: Stock réduit pour lot ID=" . $lot->getId() . ", Nouvelle quantité=" . $lot->getQuantite());
            }

            // Si la commande passe à "annulée"
            if ($entityInstance->getStatut() === 'annulee' && $ancienStatut !== 'annulee') {
                error_log("DEBUG UPDATE: Commande ID=" . $entityInstance->getId() . " passe à annulée");

                // Libérer le lot avec notre service unifié
                $this->lotLiberationService->libererLot($lot);
                $this->lotLiberationService->nettoyerLotsOrphelins(); // Auto-cleanup

                error_log("DEBUG UPDATE: Lot ID=" . $lot->getId() . " libéré automatiquement");
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Commande) {
            $lot = $entityInstance->getLot();

            // Vérifier si la commande a un lot associé
            if (!$lot) {
                error_log("DEBUG DELETE: Commande ID=" . $entityInstance->getId() . " n'a pas de lot associé - suppression simple");
                parent::deleteEntity($entityManager, $entityInstance);
                return;
            }

            error_log("DEBUG DELETE: Suppression commande ID=" . $entityInstance->getId() . ", Statut=" . $entityInstance->getStatut() . ", Lot ID=" . $lot->getId());

            // Libération simple du lot sans dépendances complexes
            try {
                // Restaurer la quantité si elle était à 0
                if ($lot->getQuantite() == 0) {
                    $lot->setQuantite(1);
                }

                // Chercher le premier utilisateur dans la file d'attente
                $fileAttente = $this->fileAttenteRepository->findOneBy(
                    ['lot' => $lot],
                    ['position' => 'ASC']
                );

                if ($fileAttente) {
                    // Réserver pour le premier utilisateur en file d'attente
                    $lot->setStatut('reserve');
                    $lot->setReservePar($fileAttente->getUser());
                    $lot->setReserveAt(new \DateTimeImmutable());
                    error_log("DEBUG DELETE: Lot réservé pour utilisateur ID=" . $fileAttente->getUser()->getId());
                } else {
                    // Libérer pour tous
                    $lot->setStatut('disponible');
                    $lot->setReservePar(null);
                    $lot->setReserveAt(null);
                    error_log("DEBUG DELETE: Lot libéré pour tous");
                }

                $entityManager->persist($lot);
                error_log("DEBUG DELETE: Lot mis à jour avec succès");
            } catch (Exception $e) {
                error_log("DEBUG DELETE: Erreur lors de la libération du lot: " . $e->getMessage());
                // Continuer avec la suppression même en cas d'erreur
            }
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }

    public function ajouterLigne(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $commandeId = $request->request->get('commande_id');
            $lotId = $request->request->get('lot_id');
            $quantite = (int) $request->request->get('quantite', 1);
            $prixUnitaire = (float) $request->request->get('prix_unitaire', 0);

            $commande = $this->entityManager->getRepository(\App\Entity\Commande::class)->find($commandeId);
            $lot = $this->lotRepository->find($lotId);

            if (!$commande) {
                $this->addFlash('danger', 'Commande introuvable.');
                return $this->redirectToRoute('admin');
            }

            if (!$lot) {
                $this->addFlash('danger', 'Lot introuvable.');
                return $this->redirectToRoute('admin');
            }

            if ($quantite > $lot->getQuantite()) {
                $this->addFlash('danger', 'Quantité demandée non disponible.');
                return $this->redirectToRoute('admin');
            }

            // Créer la ligne de commande
            $ligne = new \App\Entity\CommandeLigne();
            $ligne->setCommande($commande);
            $ligne->setLot($lot);
            $ligne->setQuantite($quantite);
            $ligne->setPrixUnitaire($prixUnitaire);
            $ligne->setPrixTotal($prixUnitaire * $quantite);

            $this->entityManager->persist($ligne);

            // Mettre à jour le total de la commande
            $commande->setPrixTotal($commande->getTotalLignes());
            $this->entityManager->persist($commande);
            $this->entityManager->flush();

            $this->addFlash('success', "Ligne ajoutée avec succès ! Total: " . number_format($commande->getPrixTotal(), 2, ',', ' ') . " €");

            return $this->redirectToRoute('admin');
        }

        // Afficher le formulaire
        $commandes = $this->entityManager->getRepository(\App\Entity\Commande::class)->findBy(['statut' => 'en_attente']);
        $lots = $this->lotRepository->findBy(['statut' => 'disponible']);

        return $this->render('admin/ajouter_ligne.html.twig', [
            'commandes' => $commandes,
            'lots' => $lots,
        ]);
    }

    public function genererPdf(Request $request): Response
    {
        $commandeId = $request->query->get('entityId');

        if (!$commandeId) {
            throw new \Exception('ID de commande manquant');
        }

        // Récupérer la commande depuis la base de données
        $commande = $this->entityManager->getRepository(Commande::class)->find($commandeId);

        if (!$commande) {
            throw new \Exception('Commande non trouvée');
        }

        // Générer le HTML de la commande
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/3tek-logo.png';
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = base64_encode(file_get_contents($logoPath));
        }

        $html = $this->renderView('admin/commande_pdf.html.twig', [
            'commande' => $commande,
            'logo_base64' => $logoBase64,
        ]);

        // Créer le PDF avec DomPDF
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'commande_' . $commande->getNumeroCommande() . '_' . date('Y-m-d') . '.pdf';

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    public function ajouterLotCommande(Request $request, Commande $commande): Response
    {
        if ($request->isMethod('POST')) {
            $lotId = $request->request->get('lot_id');
            $quantite = (int) $request->request->get('quantite', 1);
            $prixUnitaire = (float) $request->request->get('prix_unitaire', 0);

            $lot = $this->lotRepository->find($lotId);

            if (!$lot) {
                $this->addFlash('danger', 'Lot introuvable.');
                return $this->redirectToRoute('admin');
            }

            if ($quantite > $lot->getQuantite()) {
                $this->addFlash('danger', 'Quantité demandée non disponible.');
                return $this->redirectToRoute('admin');
            }

            // Vérifier si ce lot n'est pas déjà dans la commande
            $ligneExistante = $this->entityManager->getRepository(\App\Entity\CommandeLigne::class)
                ->findOneBy(['commande' => $commande, 'lot' => $lot]);

            if ($ligneExistante) {
                $this->addFlash('danger', 'Ce lot est déjà dans la commande.');
                return $this->redirectToRoute('admin');
            }

            // Créer la ligne de commande
            $ligne = new \App\Entity\CommandeLigne();
            $ligne->setCommande($commande);
            $ligne->setLot($lot);
            $ligne->setQuantite($quantite);
            $ligne->setPrixUnitaire($prixUnitaire);
            $ligne->setPrixTotal($prixUnitaire * $quantite);

            $this->entityManager->persist($ligne);

            // Mettre à jour le total de la commande
            $commande->setPrixTotal($commande->getTotalLignes());
            $this->entityManager->persist($commande);
            $this->entityManager->flush();

            $this->addFlash('success', "Lot ajouté avec succès ! Total: " . number_format($commande->getPrixTotal(), 2, ',', ' ') . " €");

            return $this->redirectToRoute('admin', [
                'crudController' => CommandeCrudController::class,
                'crudAction' => 'edit',
                'entityId' => $commande->getId()
            ]);
        }

        // Afficher le formulaire
        $lots = $this->lotRepository->findBy(['statut' => 'disponible']);

        // Exclure les lots déjà dans cette commande
        $lotsDejaDansCommande = [];
        foreach ($commande->getLignes() as $ligne) {
            $lotsDejaDansCommande[] = $ligne->getLot()->getId();
        }

        $lotsDisponibles = array_filter($lots, function ($lot) use ($lotsDejaDansCommande) {
            return !in_array($lot->getId(), $lotsDejaDansCommande);
        });

        return $this->render('admin/ajouter_lot_commande.html.twig', [
            'commande' => $commande,
            'lots' => $lotsDisponibles,
        ]);
    }
}
