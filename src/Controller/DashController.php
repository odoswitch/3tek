<?php

namespace App\Controller;


use App\Repository\LotRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;

final class DashController extends AbstractController
{
    #[Route('/dash', name: 'app_dash')]
    public function index(Request $request, LotRepository $lot, PaginatorInterface $paginator): Response
    {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }
        
        $user = $currentUser->getId();
        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('search', '');

        if($this->isGranted('ROLE_ADMIN')){
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
       }else{
            // Utilisateur normal voit uniquement ses lots
            $queryBuilder = $lot->createQueryBuilder('l')
                ->leftJoin('l.images', 'images')
                ->addSelect('images')
                ->leftJoin('l.types', 'lt')
                ->addSelect('lt')
                ->leftJoin('l.cat', 'cat')
                ->addSelect('cat')
                ->innerJoin('App\Entity\User', 'u', 'WITH', 'u.id = :userId')
                ->innerJoin('u.categorie', 'uc')
                ->innerJoin('u.type', 'ut')
                ->where('uc = l.cat')
                ->andWhere('u.isVerified = 1')
                ->andWhere('ut MEMBER OF l.types')
                ->setParameter('userId', $user);
            
            // Ajouter la recherche si présente
            if (!empty($search)) {
                $queryBuilder->andWhere('l.name LIKE :search OR l.description LIKE :search')
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
       }
    }
}
