<?php

namespace App\Controller;


use App\Repository\LotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;

final class DashController extends AbstractController
{
    #[Route('/dash', name: 'app_dash')]
    public function index(Request $request, LotRepository $lot, PaginatorInterface $paginator, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        $user = $currentUser->getId();
        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('search', '');

        if ($this->isGranted('ROLE_ADMIN')) {
            // Admin voit tous les lots avec leurs images (même quantité 0)
            $queryBuilder = $lot->createQueryBuilder('l')
                ->leftJoin('l.images', 'images')
                ->addSelect('images')
                ->leftJoin('l.types', 'types')
                ->addSelect('types')
                ->leftJoin('l.cat', 'cat')
                ->addSelect('cat');

            // Ajouter la recherche si présente
            if (!empty($search)) {
                $queryBuilder->where('l.name LIKE :search OR l.description LIKE :search OR cat.name LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }

            $queryBuilder->orderBy('l.quantite', 'DESC')
                ->addOrderBy('l.id', 'DESC');

            $query = $queryBuilder->getQuery();

            $pagination = $paginator->paginate(
                $query,
                $page,
                10
            );

            return $this->render('dash1.html.twig', [
                'controller_name' => 'DashController',
                'data' => $pagination,
                'pagination' => $pagination,
                'search' => $search,
            ]);
        } else {
            // Utilisateur normal voit ses lots disponibles et réservés
            $lots = $lot->findAvailableForUser($user, $search);

            // Ajouter les informations sur les commandes en attente pour chaque lot
            foreach ($lots as $lotItem) {
                $commandesEnAttente = $entityManager->getRepository(\App\Entity\Commande::class)
                    ->count(['lot' => $lotItem, 'statut' => 'en_attente']);
                $lotItem->commandesEnAttente = $commandesEnAttente;
            }

            // Convertir en pagination
            $pagination = $paginator->paginate(
                $lots,
                $page,
                10
            );

            return $this->render('dash1.html.twig', [
                'controller_name' => 'DashController',
                'data' => $pagination,
                'pagination' => $pagination,
                'search' => $search,
            ]);
        }
    }
}
