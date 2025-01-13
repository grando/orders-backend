<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<OrderProduct>
 */
class OrderProductRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, OrderProduct::class);
    }

    public function getOrderProduct(Order $order, Product $product): ?OrderProduct
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

    public function delete(OrderProduct $orderProduct): void
    {
        $this->entityManager->remove($orderProduct);
        $this->entityManager->flush();
    }
}
