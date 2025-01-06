<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Product::class);

    }

    public function checkAndUpdateStockLevel(Product $product, int $quantity): void
    {
        $qb = $this->createQueryBuilder('p')
            ->update()
            ->set('p.stockLevel', 'p.stockLevel - :quantity')
            ->where('p.id = :productId')
            ->andWhere('p.stockLevel >= :quantity')
            ->setParameter('quantity', $quantity)
            ->setParameter('productId', $product->getId());

        $result = $qb->getQuery()->execute();

        if ($result === 0) {
            throw new BadRequestHttpException('Quantity exceeds stock level');
        }
    }

    public function save(Product $product): void
    {
        $this->entityManager->persist($product);        
        $this->entityManager->flush();
    }
}
