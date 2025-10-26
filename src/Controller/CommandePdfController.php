<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CommandePdfController extends AbstractController
{
    #[Route('/mes-commandes/{id}/pdf', name: 'commande_pdf_client', methods: ['GET'])]
    public function genererPdfClient(int $id, CommandeRepository $commandeRepository): Response
    {
        // Récupérer la commande depuis la base de données
        $commande = $commandeRepository->find($id);

        if (!$commande) {
            throw $this->createNotFoundException('Commande non trouvée');
        }

        // Vérifier que l'utilisateur connecté est le propriétaire de la commande
        if ($commande->getUser() !== $this->getUser()) {
            throw new AccessDeniedException('Vous ne pouvez pas accéder à cette commande.');
        }

        // Générer le HTML de la commande
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/3tek-logo.png';
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = base64_encode(file_get_contents($logoPath));
        }
        
        $html = $this->renderView('client/commande_pdf.html.twig', [
            'commande' => $commande,
            'logo_base64' => $logoBase64,
        ]);

        // Créer le PDF avec DomPDF
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'ma_commande_' . $commande->getNumeroCommande() . '_' . date('Y-m-d') . '.pdf';

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }
}
