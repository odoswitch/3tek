<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lot $lot = null;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: CommandeLigne::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private \Doctrine\Common\Collections\Collection $lignes;

    #[ORM\Column]
    private ?int $quantite = 1;

    #[ORM\Column]
    private ?float $prixUnitaire = null;

    #[ORM\Column]
    private ?float $prixTotal = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = 'en_attente';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numeroCommande = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->numeroCommande = 'CMD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $this->lignes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getLot(): ?Lot
    {
        return $this->lot;
    }

    public function setLot(?Lot $lot): static
    {
        $this->lot = $lot;

        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getPrixUnitaire(): ?float
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(float $prixUnitaire): static
    {
        $this->prixUnitaire = $prixUnitaire;

        return $this;
    }

    public function getPrixTotal(): ?float
    {
        return $this->prixTotal;
    }

    public function setPrixTotal(float $prixTotal): static
    {
        $this->prixTotal = $prixTotal;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeImmutable $validatedAt): static
    {
        $this->validatedAt = $validatedAt;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getNumeroCommande(): ?string
    {
        return $this->numeroCommande;
    }

    public function setNumeroCommande(?string $numeroCommande): static
    {
        $this->numeroCommande = $numeroCommande;

        return $this;
    }

    public function getStatutLabel(): string
    {
        return match ($this->statut) {
            'en_attente' => 'En attente',
            'reserve' => 'Réservé',
            'validee' => 'Validée',
            'annulee' => 'Annulée',
            default => 'Inconnu',
        };
    }

    public function isEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }

    public function isReserve(): bool
    {
        return $this->statut === 'reserve';
    }

    public function isValidee(): bool
    {
        return $this->statut === 'validee';
    }

    public function isAnnulee(): bool
    {
        return $this->statut === 'annulee';
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, CommandeLigne>
     */
    public function getLignes(): \Doctrine\Common\Collections\Collection
    {
        return $this->lignes;
    }

    public function addLigne(CommandeLigne $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setCommande($this);
        }

        return $this;
    }

    public function removeLigne(CommandeLigne $ligne): static
    {
        if ($this->lignes->removeElement($ligne)) {
            // set the owning side to null (unless already changed)
            if ($ligne->getCommande() === $this) {
                $ligne->setCommande(null);
            }
        }

        return $this;
    }

    /**
     * Calcule le total de toutes les lignes
     */
    public function getTotalLignes(): float
    {
        $total = 0;
        foreach ($this->lignes as $ligne) {
            $total += (float) $ligne->getPrixTotal();
        }
        return $total;
    }

    /**
     * Vérifie si la commande a plusieurs lots
     */
    public function hasMultipleLots(): bool
    {
        return $this->lignes->count() > 1;
    }

    /**
     * Représentation string de la commande pour les formulaires
     */
    public function __toString(): string
    {
        $client = $this->user ? $this->user->getEmail() : 'Client inconnu';
        $lot = $this->lot ? $this->lot->getName() : 'Lot inconnu';

        return sprintf(
            '%s - %s (%s) - %s€',
            $this->numeroCommande ?? 'CMD-' . $this->id,
            $lot,
            $client,
            $this->prixTotal ? number_format($this->prixTotal, 2) : '0.00'
        );
    }
}
