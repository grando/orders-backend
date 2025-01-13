<?php

namespace App\Tests\Service;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use App\Repository\OrderProductRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\StockManagementService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StockManagementServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private StockManagementService $stockManagementService;
    private ProductRepository $productRepository;
    private OrderRepository $orderRepository;
    private OrderProductRepository $orderProductRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->orderProductRepository = $this->createMock(OrderProductRepository::class);

        $this->stockManagementService = new StockManagementService(
            $this->entityManager, 
            $this->productRepository, 
            $this->orderRepository, 
            $this->orderProductRepository
    );
    }

    public function providerCreateOrderData(): array
    {
        return [
            ['Test Order', 'This is a test order'],
            ['Another Order', null],
        ];
    }
     
    /**
     * @dataProvider providerCreateOrderData
     */
    public function testCreateOrder(string $name, ?string $description): void
    {
        $name = 'Test Order';
        $description = 'This is a test order';

        // Expect the EntityManager to persist and flush the Order entity
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($order) use ($name, $description) {
                return $order instanceof Order &&
                       $order->getName() === $name &&
                       $order->getDescription() === $description &&
                       $order->getDate() instanceof \DateTimeImmutable;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $createdOrder = $this->stockManagementService->createOrder($name, $description);

        $this->assertInstanceOf(Order::class, $createdOrder);
        $this->assertSame($name, $createdOrder->getName());
        $this->assertSame($description, $createdOrder->getDescription());
        $this->assertInstanceOf(\DateTimeImmutable::class, $createdOrder->getDate());
    }

    public function testDeleteOrder(): void
    {
        $order = new Order();
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($order);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->stockManagementService->deleteOrder($order));
    }

    public function testAddProductToOrderForExistsOrder(): void
    {
        $order = new Order();
        $product = new Product();
        $quantity = 5;
        $orderProduct = $this->createMock(OrderProduct::class);
        $orderProduct->expects($this->once())
            ->method('getQuantity')
            ->willReturn(10);

        $orderProduct->expects($this->once())
            ->method('setQuantity')
            ->with(15);

        $this->productRepository->expects($this->once())
            ->method('checkAndUpdateStockLevel')
            ->with($product, $quantity);

        $this->orderProductRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['order' => $order, 'product' => $product])
            ->willReturn($orderProduct);

        $this->entityManager->expects($this->once())
            ->method('beginTransaction');

        $this->orderProductRepository->expects($this->once())
            ->method('save');

        $order = $this->stockManagementService->addProductToOrder($order, $product, $quantity);
    }

    public function testAddProductToOrderCreatesNewOrderProduct(): void
    {
        $order = new Order();
        $product = new Product();
        $quantity = 5;

        // Mock the checkAndUpdateStockLevel method
        $this->productRepository->expects($this->once())
            ->method('checkAndUpdateStockLevel')
            ->with($product, $quantity);

        // Mock the findOneBy method to return null
        $this->orderProductRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['order' => $order, 'product' => $product])
            ->willReturn(null);

        // Mock the EntityManager methods
        $this->entityManager->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager->expects($this->once())
            ->method('commit');

        $this->entityManager->expects($this->never())
            ->method('rollback');

        // Mock the save method for OrderProductRepository and OrderRepository
        $this->orderProductRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(OrderProduct::class));

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($order);

        $order = $this->stockManagementService->addProductToOrder($order, $product, $quantity);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertCount(1, $order->getOrderProducts());
        $this->assertSame($quantity, $order->getOrderProducts()->first()->getQuantity());
    }

    public function testRemoveProductFromOrderProductNotFound(): void
    {
        $order = new Order();
        $product = new Product();
        $quantity = 5;

        $this->orderProductRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['order' => $order, 'product' => $product])
            ->willReturn(null);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Product not found in order');

        $this->stockManagementService->removeProductFromOrder($order, $product, $quantity);
    }

    public function testRemoveProductFromOrderUpdateQuantity(): void
    {
        $order = new Order();
        $product = new Product();
        $quantity = 2;

        $orderProduct = $this->createMock(OrderProduct::class);
        $orderProduct->expects($this->exactly(2))
            ->method('getQuantity')
            ->willReturn(10);

        $orderProduct->expects($this->once())
            ->method('setQuantity')
            ->with(8);

        $this->orderProductRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['order' => $order, 'product' => $product])
            ->willReturn($orderProduct);

        $this->productRepository->expects($this->once())
            ->method('increaseStockLevel')
            ->with($product, $quantity);

        $this->orderProductRepository->expects($this->once())
            ->method('save')
            ->with($orderProduct);

        $this->orderProductRepository->expects($this->never())
            ->method('delete')
            ->with($orderProduct);

        $this->entityManager->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager->expects($this->once())
            ->method('commit');

        $this->entityManager->expects($this->never())
            ->method('rollback');

        $updatedOrder = $this->stockManagementService->removeProductFromOrder($order, $product, $quantity);

        $this->assertInstanceOf(Order::class, $updatedOrder);
    }

    public function testRemoveProductFromOrderDeleteOrderProduct(): void
    {
        $order = new Order();
        $product = new Product();
        $quantity = 15;

        $orderProduct = $this->createMock(OrderProduct::class);
        $orderProduct->expects($this->once())
            ->method('getQuantity')
            ->willReturn(10);

        $this->orderProductRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['order' => $order, 'product' => $product])
            ->willReturn($orderProduct);

        $this->productRepository->expects($this->once())
            ->method('increaseStockLevel')
            ->with($product, $quantity);

        $this->orderProductRepository->expects($this->once())
            ->method('delete')
            ->with($orderProduct);

        $this->entityManager->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager->expects($this->once())
            ->method('commit');

        $this->entityManager->expects($this->never())
            ->method('rollback');

        $updatedOrder = $this->stockManagementService->removeProductFromOrder($order, $product, $quantity);

        $this->assertInstanceOf(Order::class, $updatedOrder);
    }    
}