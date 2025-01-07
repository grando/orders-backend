<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;

use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Order::class);
    }

    public function getOrderProductCustomer(Order $order, Product $product): ?OrderProduct
    {
        $query = $this->createQueryBuilder('op')
            ->where('op.order = :order')
            ->andWhere('op.product = :product')
            ->setParameter('order', $order)
            ->setParameter('product', $product)
            ->getQuery();

        return $query->getOneOrNullResult();
    }

    public function save(OrderProduct $orderProduct): void
    {
        $this->entityManager->persist($orderProduct);
        $this->entityManager->flush();
    }
}
