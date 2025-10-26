<?php

namespace App\Command;

use App\Service\LotLiberationServiceAmeliore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:nettoyer-lots-orphelins',
    description: 'Nettoie les lots réservés sans commande ni file d\'attente',
)]
class NettoyerLotsOrphelinsCommand extends Command
{
    public function __construct(
        private LotLiberationServiceAmeliore $lotLiberationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🧹 Nettoyage des lots orphelins');

        try {
            $this->lotLiberationService->nettoyerLotsOrphelins();
            
            $io->success('✅ Nettoyage terminé avec succès !');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('❌ Erreur lors du nettoyage : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

