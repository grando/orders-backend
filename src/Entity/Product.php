<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: CustomerOrderProduct::class, cascade: ['persist', 'remove'])]
    private Collection $customerOrderProducts;

    #[ORM\Column]
    private ?int $stockLevel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStockLevel(): ?int
    {
        return $this->stockLevel;
    }

    public function setStockLevel(int $stockLevel): static
    {
        $this->stockLevel = $stockLevel;

        return $this;
    }

    /**
     * @return Collection<int, CustomerOrderProduct>
     */
    public function getCustomerOrderProducts(): Collection
    {
        return $this->customerOrderProducts;
    }

    public function addOrderProduct(CustomerOrderProduct $customerOrderProduct): self
    {
        if (!$this->customerOrderProducts->contains($customerOrderProduct)) {
            $this->customerOrderProducts->add($customerOrderProduct);
            $customerOrderProduct->setProduct($this);
        }
        return $this;
    }

    public function removeCustomerOrderProduct(CustomerOrderProduct $customerOrderProduct): self
    {
        if ($this->customerOrderProducts->removeElement($customerOrderProduct)) {
            // set the owning side to null (unless already changed)
            if ($customerOrderProduct->getProduct() === $this) {
                $customerOrderProduct->setProduct(null);
            }
        }
        return $this;
    }

}
