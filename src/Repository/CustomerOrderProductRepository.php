<?php

namespace App\Repository;

use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderProduct;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;

use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerOrder>
 */
class CustomerOrderProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, CustomerOrder::class);
    }

    public function getCustomerOrderProductCustomer(CustomerOrder $customerOrder, Product $product): ?CustomerOrderProduct
    {
        $query = $this->createQueryBuilder('op')
            ->where('op.customerOrder = :customerOrder')
            ->andWhere('op.product = :product')
            ->setParameter('customerOrder', $customerOrder)
            ->setParameter('product', $product)
            ->getQuery();

        return $query->getOneOrNullResult();
    }

    public function save(CustomerOrderProduct $customerOrderProduct): void
    {
        $this->entityManager->persist($customerOrderProduct);
        $this->entityManager->flush();
    }
}
