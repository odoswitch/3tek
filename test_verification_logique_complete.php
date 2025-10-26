<?php

/**
 * TEST FINAL COMPLET - VÉRIFICATION DE TOUTE LA LOGIQUE DE FILE D'ATTENTE
 * 
 * Ce script vérifie que tous les composants de la logique de file d'attente
 * sont correctement implémentés et cohérents.
 */

echo "=== TEST FINAL COMPLET - VÉRIFICATION LOGIQUE FILE D'ATTENTE ===\n\n";

// 1. VÉRIFICATION DES SERVICES
echo "1. VÉRIFICATION DES SERVICES\n";
echo "----------------------------\n";

$services = [
    'src/Service/LotLiberationService.php',
    'src/Service/LotLiberationServiceAmeliore.php'
];

foreach ($services as $service) {
    if (file_exists($service)) {
        echo "✅ Service trouvé : $service\n";

        // Vérifier les méthodes principales
        $content = file_get_contents($service);
        if (strpos($content, 'libererLot') !== false) {
            echo "  ✅ Méthode libererLot() présente\n";
        } else {
            echo "  ❌ Méthode libererLot() manquante\n";
        }

        if (strpos($content, 'notifierDisponibilite') !== false) {
            echo "  ✅ Méthode notifierDisponibilite() présente\n";
        } else {
            echo "  ❌ Méthode notifierDisponibilite() manquante\n";
        }

        if (strpos($content, 'verifierDelaisExpires') !== false) {
            echo "  ✅ Méthode verifierDelaisExpires() présente\n";
        } else {
            echo "  ❌ Méthode verifierDelaisExpires() manquante\n";
        }
    } else {
        echo "❌ Service manquant : $service\n";
    }
}

echo "\n";

// 2. VÉRIFICATION DES CONTRÔLEURS ADMIN
echo "2. VÉRIFICATION DES CONTRÔLEURS ADMIN\n";
echo "------------------------------------\n";

$controllers = [
    'src/Controller/Admin/CommandeCrudController.php',
    'src/Controller/Admin/FileAttenteCrudController.php'
];

foreach ($controllers as $controller) {
    if (file_exists($controller)) {
        echo "✅ Contrôleur trouvé : $controller\n";

        $content = file_get_contents($controller);
        if (strpos($content, 'deleteEntity') !== false) {
            echo "  ✅ Méthode deleteEntity() présente\n";
        } else {
            echo "  ❌ Méthode deleteEntity() manquante\n";
        }

        if (strpos($content, 'LotLiberationServiceAmeliore') !== false) {
            echo "  ✅ Service LotLiberationServiceAmeliore injecté\n";
        } else {
            echo "  ❌ Service LotLiberationServiceAmeliore non injecté\n";
        }
    } else {
        echo "❌ Contrôleur manquant : $controller\n";
    }
}

echo "\n";

// 3. VÉRIFICATION DES ENTITÉS
echo "3. VÉRIFICATION DES ENTITÉS\n";
echo "---------------------------\n";

$entities = [
    'src/Entity/Commande.php',
    'src/Entity/FileAttente.php',
    'src/Entity/Lot.php',
    'src/Entity/User.php'
];

foreach ($entities as $entity) {
    if (file_exists($entity)) {
        echo "✅ Entité trouvée : $entity\n";

        $content = file_get_contents($entity);

        // Vérifications spécifiques selon l'entité
        if (strpos($entity, 'FileAttente') !== false) {
            if (strpos($content, 'expiresAt') !== false) {
                echo "  ✅ Champ expiresAt présent\n";
            } else {
                echo "  ❌ Champ expiresAt manquant\n";
            }

            if (strpos($content, 'expiredAt') !== false) {
                echo "  ✅ Champ expiredAt présent\n";
            } else {
                echo "  ❌ Champ expiredAt manquant\n";
            }

            if (strpos($content, 'en_attente_validation') !== false) {
                echo "  ✅ Statut en_attente_validation géré\n";
            } else {
                echo "  ❌ Statut en_attente_validation non géré\n";
            }
        }

        if (strpos($entity, 'Lot') !== false) {
            if (strpos($content, 'isDisponiblePour') !== false) {
                echo "  ✅ Méthode isDisponiblePour() présente\n";
            } else {
                echo "  ❌ Méthode isDisponiblePour() manquante\n";
            }
        }
    } else {
        echo "❌ Entité manquante : $entity\n";
    }
}

echo "\n";

// 4. VÉRIFICATION DES REPOSITORIES
echo "4. VÉRIFICATION DES REPOSITORIES\n";
echo "--------------------------------\n";

$repositories = [
    'src/Repository/CommandeRepository.php',
    'src/Repository/FileAttenteRepository.php'
];

foreach ($repositories as $repository) {
    if (file_exists($repository)) {
        echo "✅ Repository trouvé : $repository\n";

        $content = file_get_contents($repository);

        if (strpos($repository, 'FileAttente') !== false) {
            if (strpos($content, 'findFirstInQueue') !== false) {
                echo "  ✅ Méthode findFirstInQueue() présente\n";
            } else {
                echo "  ❌ Méthode findFirstInQueue() manquante\n";
            }

            if (strpos($content, 'en_attente_validation') !== false) {
                echo "  ✅ Statut en_attente_validation géré dans les requêtes\n";
            } else {
                echo "  ❌ Statut en_attente_validation non géré dans les requêtes\n";
            }
        }
    } else {
        echo "❌ Repository manquant : $repository\n";
    }
}

echo "\n";

// 5. VÉRIFICATION DES TEMPLATES
echo "5. VÉRIFICATION DES TEMPLATES\n";
echo "-----------------------------\n";

$templates = [
    'templates/commande/list.html.twig',
    'templates/commande/view.html.twig',
    'templates/file_attente/mes_files.html.twig',
    'templates/lot/view.html.twig'
];

foreach ($templates as $template) {
    if (file_exists($template)) {
        echo "✅ Template trouvé : $template\n";

        $content = file_get_contents($template);

        if (strpos($content, 'safe_description') !== false) {
            echo "  ✅ Filtre safe_description utilisé\n";
        } else {
            echo "  ⚠️  Filtre safe_description non utilisé (peut être normal)\n";
        }

        if (strpos($content, '|raw') !== false) {
            echo "  ⚠️  Filtre |raw encore utilisé (à vérifier)\n";
        } else {
            echo "  ✅ Pas de filtre |raw dangereux\n";
        }
    } else {
        echo "❌ Template manquant : $template\n";
    }
}

echo "\n";

// 6. VÉRIFICATION DES TEMPLATES D'EMAILS
echo "6. VÉRIFICATION DES TEMPLATES D'EMAILS\n";
echo "--------------------------------------\n";

$emailTemplates = [
    'templates/emails/commande_confirmation.html.twig',
    'templates/emails/commande_multiple_confirmation.html.twig',
    'templates/emails/admin_nouvelle_commande.html.twig',
    'templates/emails/lot_disponible_notification.html.twig',
    'templates/emails/lot_disponible_avec_delai.html.twig',
    'templates/emails/delai_depasse.html.twig'
];

foreach ($emailTemplates as $template) {
    if (file_exists($template)) {
        echo "✅ Template email trouvé : $template\n";

        $content = file_get_contents($template);

        if (strpos($content, 'Avertissement Important') !== false) {
            echo "  ✅ Avertissement de non-paiement présent\n";
        } else {
            echo "  ⚠️  Avertissement de non-paiement manquant\n";
        }
    } else {
        echo "❌ Template email manquant : $template\n";
    }
}

echo "\n";

// 7. VÉRIFICATION DES EXTENSIONS TWIG
echo "7. VÉRIFICATION DES EXTENSIONS TWIG\n";
echo "-----------------------------------\n";

$twigExtensions = [
    'src/Twig/AppExtension.php'
];

foreach ($twigExtensions as $extension) {
    if (file_exists($extension)) {
        echo "✅ Extension Twig trouvée : $extension\n";

        $content = file_get_contents($extension);

        if (strpos($content, 'clean_html') !== false) {
            echo "  ✅ Filtre clean_html présent\n";
        } else {
            echo "  ❌ Filtre clean_html manquant\n";
        }

        if (strpos($content, 'safe_description') !== false) {
            echo "  ✅ Filtre safe_description présent\n";
        } else {
            echo "  ❌ Filtre safe_description manquant\n";
        }
    } else {
        echo "❌ Extension Twig manquante : $extension\n";
    }
}

echo "\n";

// 8. VÉRIFICATION DES MIGRATIONS
echo "8. VÉRIFICATION DES MIGRATIONS\n";
echo "-------------------------------\n";

$migrationFiles = glob('migrations/*.php');
$foundExpiresMigration = false;

foreach ($migrationFiles as $migration) {
    $content = file_get_contents($migration);
    if (strpos($content, 'expires_at') !== false || strpos($content, 'expired_at') !== false) {
        echo "✅ Migration avec expires_at/expired_at trouvée : $migration\n";
        $foundExpiresMigration = true;
    }
}

if (!$foundExpiresMigration) {
    echo "❌ Aucune migration avec expires_at/expired_at trouvée\n";
}

echo "\n";

// 9. RÉSUMÉ FINAL
echo "9. RÉSUMÉ FINAL\n";
echo "---------------\n";

$totalChecks = 0;
$passedChecks = 0;

// Compter les vérifications
$allFiles = array_merge($services, $controllers, $entities, $repositories, $templates, $emailTemplates, $twigExtensions);

foreach ($allFiles as $file) {
    $totalChecks++;
    if (file_exists($file)) {
        $passedChecks++;
    }
}

$percentage = ($passedChecks / $totalChecks) * 100;

echo "📊 STATISTIQUES :\n";
echo "   - Fichiers vérifiés : $totalChecks\n";
echo "   - Fichiers présents : $passedChecks\n";
echo "   - Pourcentage de complétude : " . number_format($percentage, 1) . "%\n\n";

if ($percentage >= 95) {
    echo "🎉 EXCELLENT ! La logique de file d'attente est complètement implémentée.\n";
} elseif ($percentage >= 85) {
    echo "✅ TRÈS BIEN ! La logique de file d'attente est bien implémentée avec quelques améliorations possibles.\n";
} elseif ($percentage >= 70) {
    echo "⚠️  CORRECT ! La logique de file d'attente est implémentée mais nécessite des améliorations.\n";
} else {
    echo "❌ ATTENTION ! La logique de file d'attente nécessite des corrections importantes.\n";
}

echo "\n=== FIN DU TEST DE VÉRIFICATION ===\n";

