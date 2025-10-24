<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RgpdController extends AbstractController
{
    #[Route('/politique-confidentialite', name: 'app_privacy_policy')]
    public function privacyPolicy(): Response
    {
        return $this->render('rgpd/privacy_policy.html.twig');
    }

    #[Route('/mentions-legales', name: 'app_legal_notice')]
    public function legalNotice(): Response
    {
        return $this->render('rgpd/legal_notice.html.twig');
    }

    #[Route('/mes-donnees', name: 'app_my_data')]
    #[IsGranted('ROLE_USER')]
    public function myData(): Response
    {
        $user = $this->getUser();
        
        return $this->render('rgpd/my_data.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/supprimer-mon-compte', name: 'app_delete_account')]
    #[IsGranted('ROLE_USER')]
    public function deleteAccount(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        // Vérifier qu'il n'y a pas de commandes en cours
        if ($user->getCommandes()->count() > 0) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre compte car vous avez des commandes en cours. Veuillez contacter l\'administrateur.');
            return $this->redirectToRoute('app_my_data');
        }

        // Anonymiser les données au lieu de supprimer complètement
        $user->setEmail('deleted_' . uniqid() . '@deleted.com');
        $user->setName('Utilisateur');
        $user->setLastname('Supprimé');
        $user->setPhone('0000000000');
        $user->setOffice('N/A');
        $user->setRoles(['ROLE_DELETED']);
        
        $entityManager->flush();

        // Déconnecter l'utilisateur
        $this->container->get('security.token_storage')->setToken(null);

        $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
        
        return $this->redirectToRoute('app_login');
    }

    #[Route('/exporter-mes-donnees', name: 'app_export_data')]
    #[IsGranted('ROLE_USER')]
    public function exportData(): Response
    {
        $user = $this->getUser();
        
        // Récupérer les catégories
        $categories = [];
        foreach ($user->getCategorie() as $cat) {
            $categories[] = $cat->getName();
        }
        
        $data = [
            'Informations personnelles' => [
                'Email' => $user->getEmail(),
                'Nom' => $user->getName(),
                'Prénom' => $user->getLastname(),
                'Téléphone' => $user->getPhone(),
                'Bureau' => $user->getOffice(),
                'Catégories' => !empty($categories) ? implode(', ', $categories) : 'Aucune',
                'Type' => $user->getType() ? $user->getType()->getName() : 'Aucun',
            ],
            'Commandes' => [],
            'Favoris' => [],
        ];

        // Ajouter les commandes
        foreach ($user->getCommandes() as $commande) {
            $data['Commandes'][] = [
                'Numéro' => $commande->getNumeroCommande(),
                'Date' => $commande->getCreatedAt()->format('d/m/Y H:i'),
                'Montant' => $commande->getPrixTotal() . ' €',
                'Statut' => $commande->getStatut(),
            ];
        }

        // Ajouter les favoris
        foreach ($user->getFavoris() as $favori) {
            $data['Favoris'][] = [
                'Lot' => $favori->getLot()->getName(),
                'Date d\'ajout' => $favori->getCreatedAt()->format('d/m/Y H:i'),
            ];
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $response = new Response($json);
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="mes_donnees_3tek.json"');

        return $response;
    }
}
