<?php

namespace App\Entity;

use App\Repository\CustomerOrderProductRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerOrderProductRepository::class)]
#[ORM\Table(name: 'customer_order_product', indexes: [
    new ORM\Index(name: 'customer_order_product_idx', columns: ['customer_order_id', 'product_id']),
])]
class CustomerOrderProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CustomerOrder::class, inversedBy: 'customerOrderProducts')]
    #[ORM\JoinColumn(name: 'customer_order_id', nullable: false)]
    private ?CustomerOrder $customerOrder = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'customerOrderProducts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\Column]
    private ?int $quantity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomerOrder(): ?CustomerOrder
    {
        return $this->customerOrder;
    }

    public function setCustomerOrder(?CustomerOrder $customerOrder): self
    {
        $this->customerOrder = $customerOrder;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }
}