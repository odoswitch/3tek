<?php

namespace App\Entity;


##use Assert\Type;
use App\Entity\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\LotRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Vich\UploaderBundle\Mapping\Annotation as Vich;



#[ORM\Entity(repositoryClass: LotRepository::class)]
#[Vich\Uploadable]
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

    #[ORM\Column(length: 255, nullable: true)]

    private ?string $image = null;




    #[Vich\UploadableField(mapping: 'lot_images', fileNameProperty: 'image')]
    ##[ORM\Column(nullable: true)]
    private ?File $imageFile = null;



    ##[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\ManyToOne(inversedBy: 'lots')]
    private ?Category $cat = null;

    /**
     * @var Collection<int, Type>
     */
    #[ORM\ManyToMany(targetEntity: Type::class, inversedBy: 'lots')]
    private Collection $types;

    #[ORM\Column]
    private ?float $prix = null;

    #[ORM\Column]
    private ?int $quantite = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, LotImage>
     */
    #[ORM\OneToMany(targetEntity: LotImage::class, mappedBy: 'lot', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $images;

    public function __construct()
    {
        $this->types = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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

    public function setImage(?string $image): static
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
     * If manually uploading a file (i.e. not using Symfony Form's UploadedFile representation),
     * this must be called to ensure that the file's contents are permanently saved to disk.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     */
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        // TRÈS IMPORTANT : Mettre à jour un champ mappé si un nouveau fichier est fourni.
        // Cela force Doctrine à détecter un changement et à déclencher les listeners de Vich.

    }

    /**
     * @return Collection<int, LotImage>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(LotImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setLot($this);
        }

        return $this;
    }

    public function removeImage(LotImage $image): static
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getLot() === $this) {
                $image->setLot(null);
            }
        }

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

    public function isAvailable(): bool
    {
        return $this->quantite > 0;
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

    /**
     * Get the main image (first image or fallback to old image field)
     */
    public function getMainImage(): ?string
    {
        if ($this->images->count() > 0) {
            return $this->images->first()->getImageName();
        }
        return $this->image;
    }
}
