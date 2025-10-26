<?php

namespace App\Controller;

use App\Entity\FileAttente;
use App\Entity\Lot;
use App\Repository\FileAttenteRepository;
use App\Repository\LotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/file-attente')]
class FileAttenteController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer
    ) {}

    #[Route('/rejoindre/{lotId}', name: 'app_file_attente_rejoindre', methods: ['POST'])]
    public function rejoindre(int $lotId, Request $request, LotRepository $lotRepository, FileAttenteRepository $fileAttenteRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $lot = $lotRepository->find($lotId);
        if (!$lot) {
            $this->addFlash('error', 'Lot introuvable');
            return $this->redirectToRoute('app_dash');
        }

        // Vérifier si le lot est réservé ou vendu
        if ($lot->getStatut() !== 'reserve' && $lot->getStatut() !== 'vendu') {
            $this->addFlash('error', 'Ce lot n\'est pas réservé, vous pouvez le commander directement');
            return $this->redirectToRoute('app_lot_view', ['id' => $lotId]);
        }

        // Vérifier si l'utilisateur est déjà dans la file d'attente
        $existingEntry = $fileAttenteRepository->findOneBy([
            'user' => $user,
            'lot' => $lot,
            'statut' => 'en_attente'
        ]);

        if ($existingEntry) {
            $this->addFlash('info', 'Vous êtes déjà dans la file d\'attente pour ce lot');
            return $this->redirectToRoute('app_lot_view', ['id' => $lotId]);
        }

        // Vérifier si l'utilisateur est le propriétaire de la réservation
        if ($lot->getReservePar() === $user) {
            $this->addFlash('info', 'Vous avez déjà réservé ce lot');
            return $this->redirectToRoute('app_lot_view', ['id' => $lotId]);
        }

        // Obtenir la prochaine position dans la file
        $lastPosition = $fileAttenteRepository->getLastPositionForLot($lot);
        $position = $lastPosition + 1;

        // Créer l'entrée dans la file d'attente
        $fileAttente = new FileAttente();
        $fileAttente->setUser($user);
        $fileAttente->setLot($lot);
        $fileAttente->setPosition($position);
        $fileAttente->setStatut('en_attente');

        $this->entityManager->persist($fileAttente);
        $this->entityManager->flush();

        $this->addFlash('success', "Vous avez rejoint la file d'attente en position {$position}. Vous serez notifié si le lot devient disponible.");

        return $this->redirectToRoute('app_lot_view', ['id' => $lotId]);
    }

    #[Route('/quitter/{id}', name: 'app_file_attente_quitter')]
    public function quitter(FileAttente $fileAttente): Response
    {
        $user = $this->getUser();
        if (!$user || $fileAttente->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($fileAttente);
        $this->entityManager->flush();

        $this->addFlash('success', 'Vous avez quitté la file d\'attente');
        return $this->redirectToRoute('app_mes_files_attente');
    }

    #[Route('/mes-files', name: 'app_mes_files_attente')]
    public function mesFiles(FileAttenteRepository $fileAttenteRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $files = $fileAttenteRepository->findByUser($user);

        return $this->render('file_attente/mes_files.html.twig', [
            'files' => $files,
        ]);
    }
}
