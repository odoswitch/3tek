<?php

namespace App\Entity;

use App\Repository\TypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeRepository::class)]
class Type
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Lot>
     */
    #[ORM\ManyToMany(targetEntity: Lot::class, mappedBy: 'types')]
    private Collection $lots;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'type')]
    private Collection $users;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function __construct()
    {
        $this->lots = new ArrayCollection();
        $this->users = new ArrayCollection();
    }
    public function __toString()
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Lot>
     */
    public function getLots(): Collection
    {
        return $this->lots;
    }

    public function addLot(Lot $lot): static
    {
        if (!$this->lots->contains($lot)) {
            $this->lots->add($lot);
            $lot->addType($this);
        }

        return $this;
    }

    public function removeLot(Lot $lot): static
    {
        if ($this->lots->removeElement($lot)) {
            $lot->removeType($this);
        }

        return $this;
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

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setType($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            if ($user->getType() === $this) {
                $user->setType(null);
            }
        }

        return $this;
    }
}
