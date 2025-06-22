<?php

namespace App\Entity;


use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\LotRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Vich\UploaderBundle\Mapping\Annotation as Vich;


#[ORM\Entity(repositoryClass: LotRepository::class)]
class Lot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;



    #[Vich\UploadableField(mapping: 'lot_images', fileNameProperty: 'image')]

    /** 
     * @Vich\UploadableField(mapping: 'lot_images', fileNameProperty: 'image')
     */



    private ?File $imageFile = null;



    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Category $cat = null;

    /**
     * @var Collection<int, Type>
     */
    #[ORM\ManyToMany(targetEntity: Type::class, inversedBy: 'lots')]
    private Collection $types;

    #[ORM\Column]
    private ?float $prix = null;

    public function __construct()
    {
        $this->types = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getCat(): ?Category
    {
        return $this->cat;
    }

    public function setCat(?Category $cat): static
    {
        $this->cat = $cat;

        return $this;
    }

    /**
     * @return Collection<int, Type>
     */
    public function getTypes(): Collection
    {
        return $this->types;
    }

    public function addType(Type $type): static
    {
        if (!$this->types->contains($type)) {
            $this->types->add($type);
        }

        return $this;
    }

    public function removeType(Type $type): static
    {
        $this->types->removeElement($type);

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }


    /**
     * Getter pour le fichier uploadé (temporaire).
     * @return File|null
     */


    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    /**
     * Setter pour le fichier uploadé (temporaire).
     * Si un fichier est fourni, met à jour la date de modification
     * pour déclencher les listeners de Vich.
     */
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        // TRÈS IMPORTANT : Mettre à jour un champ mappé si un nouveau fichier est fourni.
        // Cela force Doctrine à détecter un changement et à déclencher les listeners de Vich.

    }
}
