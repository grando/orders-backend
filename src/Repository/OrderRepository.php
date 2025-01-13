<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Order::class);
    }

    public function findByCriteria(?string $name, ?string $description, int $page = 1, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('o');

        if ($name) {
            $qb->andWhere('o.name LIKE :name')
               ->setParameter('name', '%' . $name . '%');
        }

        if ($description) {
            $qb->andWhere('o.description LIKE :description')
               ->setParameter('description', '%' . $description . '%');
        }

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function updateOrder(Order $order, ?string $name, ?string $desc): Order
    {
        if (null != $name) {
            $order->setName($name);
        }
        if (null != $desc) {
            $order->setDescription($desc);
        }        
        $this->save($order);
        return $order;
    }

    public function save(Order $order): void
    {
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

}
