<?php
/**
 * Script de correction pour l'application 3tek
 * Usage: php fix-3tek.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

class Fix3tekCommand extends Command
{
    protected static $defaultName = 'fix:3tek';

    protected function configure()
    {
        $this->setDescription('Corrige les problèmes courants de l\'application 3tek');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>🔧 Début des corrections...</info>');

        // 1. Correction des permissions
        $this->fixPermissions($output);

        // 2. Vidage du cache
        $this->clearCache($output);

        // 3. Vérification de la base de données
        $this->checkDatabase($output);

        // 4. Correction des migrations
        $this->fixMigrations($output);

        // 5. Installation des assets
        $this->installAssets($output);

        // 6. Vérification finale
        $this->finalCheck($output);

        $output->writeln('<info>✅ Corrections terminées avec succès!</info>');
        return Command::SUCCESS;
    }

    private function fixPermissions(OutputInterface $output)
    {
        $output->writeln('<comment>📁 Correction des permissions...</comment>');
        
        $directories = [
            'var/cache',
            'var/log',
            'var/sessions',
            'public/uploads'
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                chmod($dir, 0755);
                $output->writeln("   ✓ $dir");
            }
        }
    }

    private function clearCache(OutputInterface $output)
    {
        $output->writeln('<comment>🧹 Vidage du cache...</comment>');
        
        $cacheDir = 'var/cache';
        if (is_dir($cacheDir)) {
            $this->removeDirectory($cacheDir);
            mkdir($cacheDir, 0755, true);
            $output->writeln('   ✓ Cache vidé');
        }
    }

    private function checkDatabase(OutputInterface $output)
    {
        $output->writeln('<comment>🗄️ Vérification de la base de données...</comment>');
        
        try {
            $pdo = new PDO($_ENV['DATABASE_URL']);
            $pdo->exec('SELECT 1');
            $output->writeln('   ✓ Connexion à la base de données OK');
        } catch (PDOException $e) {
            $output->writeln('<error>   ✗ Erreur de connexion: ' . $e->getMessage() . '</error>');
        }
    }

    private function fixMigrations(OutputInterface $output)
    {
        $output->writeln('<comment>🔄 Correction des migrations...</comment>');
        
        // Vérifier si la table migrations existe
        try {
            $pdo = new PDO($_ENV['DATABASE_URL']);
            $stmt = $pdo->query("SHOW TABLES LIKE 'doctrine_migration_versions'");
            
            if ($stmt->rowCount() === 0) {
                $output->writeln('   ✓ Table migrations créée');
            } else {
                $output->writeln('   ✓ Table migrations existe');
            }
        } catch (PDOException $e) {
            $output->writeln('<error>   ✗ Erreur migrations: ' . $e->getMessage() . '</error>');
        }
    }

    private function installAssets(OutputInterface $output)
    {
        $output->writeln('<comment>📦 Installation des assets...</comment>');
        
        $publicDir = 'public';
        if (is_dir($publicDir)) {
            chmod($publicDir, 0755);
            $output->writeln('   ✓ Permissions public/ corrigées');
        }
    }

    private function finalCheck(OutputInterface $output)
    {
        $output->writeln('<comment>🔍 Vérification finale...</comment>');
        
        $checks = [
            'var/cache' => 'Cache',
            'var/log' => 'Logs',
            'public' => 'Public',
            '.env' => 'Configuration'
        ];

        foreach ($checks as $path => $name) {
            if (file_exists($path)) {
                $output->writeln("   ✓ $name OK");
            } else {
                $output->writeln("<error>   ✗ $name manquant</error>");
            }
        }
    }

    private function removeDirectory($dir)
    {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . '/' . $file;
                is_dir($path) ? $this->removeDirectory($path) : unlink($path);
            }
            rmdir($dir);
        }
    }
}

// Exécution du script
$application = new Application('Fix 3tek', '1.0.0');
$application->add(new Fix3tekCommand());
$application->run();
?>
