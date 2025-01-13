<?php

namespace App\Tests\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Repository\OrderProductRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\StockManagementService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrdersControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private OrderRepository $orderRepository;
    private ProductRepository $productRepository;
    private OrderProductRepository $orderProductRepository;
    private StockManagementService $stockManagementService;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private Serializer $serializer;
    private NormalizerInterface $normalizer;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Mock the repository and service dependencies
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->orderProductRepository = $this->createMock(OrderProductRepository::class);
        $this->stockManagementService = $this->createMock(StockManagementService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->normalizer = self::getContainer()->get('serializer.normalizer.object');
        $this->serializer = self::getContainer()->get('serializer');

        // Replace the real services with the mocks
        self::getContainer()->set(OrderRepository::class, $this->orderRepository);
        self::getContainer()->set(ProductRepository::class, $this->productRepository);
        self::getContainer()->set(OrderProductRepository::class, $this->orderProductRepository);
        self::getContainer()->set(StockManagementService::class, $this->stockManagementService);
        self::getContainer()->set(EntityManagerInterface::class, $this->entityManager);
        self::getContainer()->set(ValidatorInterface::class, $this->validator);
    }

    public function provideInvalidData(): array
    {
        $order1 = ['id' => 1, 'name' => 'Order 1', 'description' => 'Description 1'];
        $order2 = ['id' => 2, 'name' => 'Order 2', 'description' => 'Description 2'];
        $expected = [
            'status' => 'success',
            'data' => [],
            'message' => 'Orders retrieved successfully'
        ];
    
        return [
            'no filters' => [
                [$order1, $order2], 
                array_merge($expected, ['data' => [$order1, $order2]]),
                ['page' => 1, 'limit' => 10]
            ],
            'filters by name' => [
                [$order1], 
                array_merge($expected, ['data' => [$order1]]),
                ['name' =>'Order 1', 'page' => 1, 'limit' => 10]
            ],
            'filters by description' => [
                [$order2], 
                array_merge($expected, ['data' => [$order2]]),
                ['description' =>'Description 2', 'page' => 1, 'limit' => 10]
            ],
            'invalid page' => [
                [],  
                $expected,
                ['page' => -1, 'limit' => 10]
            ],
            'invalid limit' => [
                [],
                $expected,
                ['page' => 1, 'limit' => 0]
            ],
        ];
    }

    /**
     * @dataProvider provideInvalidData
     */
    public function testIndex(array $orders, array $expected, array $filters ): void
    {
        // Configure the repository mock
        $this->orderRepository->expects($this->once())
            ->method('findByCriteria')
            ->with(null, null, 1, 10)
            ->willReturn($orders);

        // Make the request
        $this->client->request('GET', '/orders');

        // Assert the response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());        
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $this->client->getResponse()->getContent());
    }

    public function testCreate(): void
    {
        // Define the input data
        $data = [
            'name' => 'Test Order',
            'description' => 'This is a test order'
        ];

        // Define the expected order object
        $order = new Order();
        $order->setId(1);
        $order->setName('Test Order');
        $order->setDescription('This is a test order');
        
        // Configure the validator mock to return no errors
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($data)
            ->willReturn(new ConstraintViolationList());

        // Configure the stockManagementService mock to return the expected order
        $this->stockManagementService->expects($this->once())
            ->method('createOrder')
            ->with($data['name'], $data['description'])
            ->willReturn($order);

        // Make the request
        $this->client->request('POST', '/orders', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        // Assert the response
        $expected = [
            "status"=> "success",
            "data" => [
                "id" => 1,
                "name" => "Test Order",
                "description" => "This is a test order"
            ],
            "message" => "Order created successfully"
        ];
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());        
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $this->client->getResponse()->getContent());
    }

    public function testUpdateSuccess(): void
    {
        // Define the input data
        $data = [
            'name' => 'Updated Order',
            'description' => 'This is an updated test order'
        ];

        // Define the existing order object
        $order = new Order();
        $order->setId(1);
        $order->setName('Test Order');
        $order->setDescription('This is a test order');

        // Configure the repository mock to return the existing order
        $this->orderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($order);

        // Configure the validator mock to return no errors
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($data)
            ->willReturn(new ConstraintViolationList());

        // Configure the repository mock to update the order
        $this->orderRepository->expects($this->once())
            ->method('updateOrder')
            ->with($order, $data['name'], $data['description'])
            ->willReturn($order);

        // Make the request
        $this->client->request('PUT', '/orders/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $expected = [
            'status' => 'success',
            'data' => [
                'id' => 1,
                'name' => 'Updated Order',
                'description' => 'This is an updated test order'
            ],
            'message' => 'Order updated successfully'
        ];

        // Assert the response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString($this->normalizer->normalize(json_encode($expected), 'json', ['groups' => ['list']]), $this->client->getResponse()->getContent());
    }
    
    public function testUpdateValidationError(): void
    {
        // Define the input data
        $data = [
            'name' => '',
            'description' => 'This is an updated test order'
        ];

        // Define the existing order object
        $order = new Order();
        $order->setId(1);
        $order->setName('Test Order');
        $order->setDescription('This is a test order');

        // Configure the repository mock to return the existing order
        $this->orderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($order);

        // Configure the validator mock to return errors
        $violations = new ConstraintViolationList([
            new \Symfony\Component\Validator\ConstraintViolation('This value should not be blank.', '', [], '', 'name', '')
        ]);
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($data)
            ->willReturn($violations);

        // Make the request
        $this->client->request('PUT', '/orders/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        // Assert the response
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(['message' => 'Validation error for order creation', 'errors' => ['name' => 'This value should not be blank.']]), $this->client->getResponse()->getContent());
    }

    public function testOrderDetailsWithSuccess(): void
    {
        // Define the existing order object
        $order = new Order();
        $order->setId(1);
        $order->setName('Test Order');
        $order->setDescription('This is a test order');

        // Configure the repository mock to return the existing order
        $this->orderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($order);

        // Configure the serializer mock to return the expected JSON data
        $expectedData = json_encode([
            'id' => 1,
            'name' => 'Test Order',
            'description' => 'This is a test order',
            'orderProducts' => []
        ]);

        // Make the request
        $this->client->request('GET', '/orders/1');

        // Assert the response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString($expectedData, $this->client->getResponse()->getContent());
    }

    public function testOrderDetailsReturnNotFound(): void
    {
        // Configure the repository mock to return null
        $this->orderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willThrowException(new EntityNotFoundException('Order not found'));

        // Make the request
        $this->client->request('GET', '/orders/1');

        $expected = [
            'status' => 'error',
            'message' => 'Order details retrieval failed',
            'errors' => ['Order not found']
        ];

        // Assert the response
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $this->client->getResponse()->getContent());
    }

    public function testDeleteWithSuccess(): void
    {
        // Define the existing order object
        $order = new Order();
        $order->setId(1);
        $order->setName('Test Order');
        $order->setDescription('This is a test order');

        // Configure the repository mock to return the existing order
        $this->orderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($order);

        // Configure the stockManagementService mock to return true for deletion
        $this->stockManagementService->expects($this->once())
            ->method('deleteOrder')
            ->with($order)
            ->willReturn(true);

        // Make the request
        $this->client->request('DELETE', '/orders/1');

        $expected = [
            'status' => 'success',
            'data' => null,
            'message' => 'Order deleted successfully'
        ];

        // Assert the response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $this->client->getResponse()->getContent());
    }

    public function testDeleteReturnNotFound(): void
    {
        // Configure the repository mock to return null
        $this->orderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willThrowException(new EntityNotFoundException('Order not found'));

        // Make the request
        $this->client->request('DELETE', '/orders/1');

        $expected = [
            'status' => 'error',
            'message' => 'Order deletion exception',
            'errors' => ['Order not found']
        ];

        // Assert the response
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $this->client->getResponse()->getContent());
    }

    public function testAddProductWillSuccess(): void
    {
        // Define the input data
        $data = [
            'quantity' => 5
        ];

        // Define the existing order and product objects
        $order = new Order();
        $order->setId(1);
        $order->setName('Test Order');
        $order->setDescription('This is a test order');

        $product = new Product();
        $product->setId(1);
        $product->setName('Test Product');

        // Configure the repository mocks to return the existing order and product
        $this->orderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($order);

        $this->productRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        // Configure the validator mock to return no errors
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($data)
            ->willReturn(new ConstraintViolationList());

        // Configure the stockManagementService mock to add the product to the order
        $this->stockManagementService->expects($this->once())
            ->method('addProductToOrder')
            ->with($order, $product, $data['quantity'])
            ->willReturn($order);

        // Make the request
        $this->client->request('PUT', '/orders/1/products/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $exceped = [
            'status' => 'success',
            'data' => [
                'id' => 1,
                'name' => 'Test Order',
                'description' => 'This is a test order'
            ],
            'message' => 'Product added to order successfully'
        ];

        // Assert the response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($exceped), $this->client->getResponse()->getContent());
    }    

    public function testAddProductValidationError(): void
    {
        // Define the input data with a validation error
        $data = [
            'quantity' => -5
        ];

        // Define the existing order and product objects
        $order = new Order();
        $order->setId(1);
        $order->setName('Test Order');
        $order->setDescription('This is a test order');

        $product = new Product();
        $product->setId(1);
        $product->setName('Test Product');
        
        // Configure the repository mocks to return the existing order and product
        $this->orderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($order);

        // Configure the validator mock to return validation errors
        $violations = new ConstraintViolationList([
            new ConstraintViolation('This value should be positive.', '', [], '', 'quantity', -5)
        ]);
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($data)
            ->willReturn($violations);

        // Make the request
        $this->client->request('PUT', '/orders/1/products/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $exceped = [
            'status' => 'error',
            'message' => 'Validation error for product quantity',
            'errors' => [
                'quantity: This value should be positive.'
            ]
        ];

        // Assert the response
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($exceped), $this->client->getResponse()->getContent());
    }

    public function testAddProductOrderNotFound(): void
    {
        // Define the input data
        $data = [
            'quantity' => 5
        ];

        // Configure the repository mock to throw an exception for the order
        $this->orderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willThrowException(new EntityNotFoundException('Order not found'));

        // Make the request
        $this->client->request('PUT', '/orders/1/products/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $exceped = [
            'status' => 'error',
            'message' => 'Exception while adding product to order',
            'errors' => ['Order not found']
        ];

        // Assert the response
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($exceped), $this->client->getResponse()->getContent());
    }

    public function testAddProductProductNotFound(): void
    {
        // Define the input data
        $data = [
            'quantity' => 5
        ];

        // Define the existing order object
        $order = new Order();
        $order->setId(1);
        $order->setName('Test Order');
        $order->setDescription('This is a test order');

        // Configure the repository mock to return the existing order and throw an exception for the product
        $this->orderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($order);

        $this->productRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willThrowException(new EntityNotFoundException('Product not found'));

        // Make the request
        $this->client->request('PUT', '/orders/1/products/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $exceped = [
            'status' => 'error',
            'message' => 'Exception while adding product to order',
            'errors' => ['Product not found']
        ];

        // Assert the response
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($exceped), $this->client->getResponse()->getContent());
    }

    public function testRemoveProductSuccess(): void
    {
        // Define the input data
        $data = [
            'quantity' => 5
        ];

        // Define the existing order and product objects
        $order = new Order();
        $order->setId(1);
        $order->setName('Test Order');
        $order->setDescription('This is a test order');

        $product = new Product();
        $product->setId(1);
        $product->setName('Test Product');

        // Configure the repository mocks to return the existing order and product
        $this->orderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($order);

        $this->productRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        // Configure the validator mock to return no errors
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($data)
            ->willReturn(new ConstraintViolationList());

        // Configure the stockManagementService mock to remove the product from the order
        $this->stockManagementService->expects($this->once())
            ->method('removeProductFromOrder')
            ->with($order, $product, $data['quantity']);

        // Make the request
        $this->client->request('DELETE', '/orders/1/products/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $expected = [
            'status' => 'success',
            'data' => [
                'id' => 1,
                'name' => 'Test Order',
                'description' => 'This is a test order'
            ],
            'message' => 'Product removed from order successfully'
        ];

        // Assert the response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $this->client->getResponse()->getContent());
    }
    
    public function testRemoveProductValidationError(): void
    {
        // Define the input data with a validation error
        $data = [
            'quantity' => -5
        ];

        // Define the existing order and product objects
        $order = new Order();
        $order->setId(1);
        $order->setName('Test Order');
        $order->setDescription('This is a test order');

        $product = new Product();
        $product->setId(1);
        $product->setName('Test Product');

        // Configure the repository mocks to return the existing order and product
        $this->orderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($order);

        // Configure the validator mock to return validation errors
        $violations = new ConstraintViolationList([
            new ConstraintViolation('This value should be positive.', '', [], '', 'quantity', -5)
        ]);
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($data)
            ->willReturn($violations);

        // Make the request
        $this->client->request('DELETE', '/orders/1/products/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $expected = [
            'status' => 'error',
            'message' => 'Validation error for product quantity',
            'errors' => [
                'quantity: This value should be positive.'
            ]
        ];

        // Assert the response
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $this->client->getResponse()->getContent());
    }

    public function testRemoveProductOrderNotFound(): void
    {
        // Define the input data
        $data = [
            'quantity' => 5
        ];

        // Configure the repository mock to throw an exception for the order
        $this->orderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willThrowException(new EntityNotFoundException('Order not found'));

        // Make the request
        $this->client->request('DELETE', '/orders/1/products/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $expected = [
            'status' => 'error',
            'message' => 'Exception while removing product from order',
            'errors' => ['Order not found']
        ];

        // Assert the response
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $this->client->getResponse()->getContent());
    }

    public function testRemoveProductProductNotFound(): void
    {
        // Define the input data
        $data = [
            'quantity' => 5
        ];

        // Define the existing order object
        $order = new Order();
        $order->setId(1);
        $order->setName('Test Order');
        $order->setDescription('This is a test order');

        // Configure the repository mock to return the existing order and throw an exception for the product
        $this->orderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($order);

        $this->productRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willThrowException(new EntityNotFoundException('Product not found'));

        // Make the request
        $this->client->request('DELETE', '/orders/1/products/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $expected = [
            'status' => 'error',
            'message' => 'Exception while removing product from order',
            'errors' => ['Product not found']
        ];

        // Assert the response
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $this->client->getResponse()->getContent());
    }
}