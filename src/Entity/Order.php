<?php

namespace App\Entity;

use App\Repository\CustomerOrderRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CustomerOrderRepository::class)]
class CustomerOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['list'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['list'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['list'])]
    private ?\DateTimeImmutable $date = null;

    #[ORM\OneToMany(mappedBy: 'customerOrder', targetEntity: CustomerOrderProduct::class, cascade: ['persist', 'remove'])]
    private Collection $customerOrderProducts;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return Collection<int, CustomerOrderProduct>
     */
    public function getCustomerOrderProducts(): Collection
    {
        return $this->customerOrderProducts;
    }

    public function addCustomerOrderProduct(CustomerOrderProduct $customerOrderProduct): self
    {
        if (!$this->customerOrderProducts->contains($customerOrderProduct)) {
            $this->customerOrderProducts->add($customerOrderProduct);
            $customerOrderProduct->setCustomerOrder($this);
        }
        return $this;
    }

    public function removeCustomerOrderProduct(CustomerOrderProduct $customerOrderProduct): self
    {
        if ($this->customerOrderProducts->removeElement($customerOrderProduct)) {
            // set the owning side to null (unless already changed)
            if ($customerOrderProduct->getCustomerOrder() === $this) {
                $customerOrderProduct->setCustomerOrder(null);
            }
        }
        return $this;
    }
}
