<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use App\Repository\OrderProductRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class StockManagementService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        private OrderRepository $orderRepository,
        private OrderProductRepository $orderProductRepository
    )
    {
    }

    public function createOrder(string $name, ?string $description): Order
    {
        $order = new Order();
        $order->setName($name)
            ->setDescription($description)
            ->setDate(new \DateTimeImmutable());
        
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    public function deleteOrder(Order $order): bool
    {
        try{
            $this->entityManager->remove($order);
            $this->entityManager->flush();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }


    public function addProductToOrder(Order $order, Product $product, int $quantity): Order
    {
        $this->entityManager->beginTransaction();

        try {

            
            
            
            // Check and update stock level
            $this->productRepository->checkAndUpdateStockLevel($product, $quantity);

            // Create or update the OrderProduct entity
            $orderProduct = $this->orderProductRepository->findOneBy(['order' => $order, 'product' => $product]);
die('here);
            if ($orderProduct) {
                $orderProduct->setQuantity($orderProduct->getQuantity() + $quantity);
            } else {
                $orderProduct = new OrderProduct();
                $orderProduct->setOrder($order);
                $orderProduct->setProduct($product);
                $orderProduct->setQuantity($quantity);
                $order->addOrderProduct($orderProduct);
            }

            $this->orderProductRepository->save($orderProduct);
            $this->orderRepository->save($order);

            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        return $order;
    }

}