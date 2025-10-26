<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('clean_html', [$this, 'cleanHtml']),
            new TwigFilter('safe_description', [$this, 'safeDescription']),
        ];
    }

    /**
     * Nettoie le HTML en gardant seulement les balises sûres
     */
    public function cleanHtml(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Liste des balises autorisées
        $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6>';

        // Supprime toutes les balises sauf celles autorisées
        $cleaned = strip_tags($html, $allowedTags);

        // Nettoie les attributs des balises restantes
        $cleaned = preg_replace('/<([^>]+)\s+[^>]*>/', '<$1>', $cleaned);

        return $cleaned;
    }

    /**
     * Affiche une description de manière sûre pour l'affichage
     */
    public function safeDescription(?string $description, int $maxLength = null): string
    {
        if (empty($description)) {
            return '';
        }

        // Nettoie le HTML
        $cleaned = $this->cleanHtml($description);

        // Si une longueur maximale est spécifiée, tronque le texte
        if ($maxLength && strlen(strip_tags($cleaned)) > $maxLength) {
            $textOnly = strip_tags($cleaned);
            $truncated = substr($textOnly, 0, $maxLength);
            $cleaned = $truncated . '...';
        }

        return $cleaned;
    }
}

