<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Model\ApiResponse;
use App\Repository\OrderProductRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\StockManagementService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Nelmio\ApiDocBundle\Attribute\Model;
use SebastianBergmann\CodeUnit\CodeUnit;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use OpenApi\Attributes as OA;

#[Route('/orders', name: 'app_orders_')]
class OrdersController extends AbstractController
{
    public function __construct(
        private StockManagementService $stockManagementService,
        private SerializerInterface $serializer,
        private OrderRepository $orderRepository,
        private ProductRepository $productRepository,
        private ValidatorInterface $validator,
        private EntityManagerInterface $entityManager,
        private OrderProductRepository $orderProductRepository
    )
    {
    }
    #[OA\Get(
        path: '/orders',
        summary: 'List all orders',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Returns the list of orders',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: Order::class, groups: ['list'])))
            )
        ]
    )]
    #[Route('', name: 'list', methods: ['GET'])]
    public function index(Request $request, NormalizerInterface $normalizer): JsonResponse
    {
        try{ 
            $name = $request->query->get('name');
            $description = $request->query->get('description');
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 10);

            $orders = $this->orderRepository->findByCriteria($name, $description, $page, $limit);
            $data = $normalizer->normalize($orders, null, ['groups' => ['list']]);

            return $this->successResponse($data, 'Orders retrieved successfully');

        } catch (\Throwable $e) {
            return $this->errorResponse('Orders retrieval failed', [$e->getMessage()]);
        }
    }

    #[OA\Post(
        path: '/orders',
        summary: 'Create a new order',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: Order::class, groups: ['create']))
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Order created successfully',
                content: new OA\JsonContent(ref: new Model(type: Order::class, groups: ['details']))
            )
        ]
    )]
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try{
            $data = json_decode($request->getContent(), true);            
            
            $errors = $this->validateOrderData($data);                  
            if ($errors->count() > 0) {                
                $errorsList = $this->errorsListToArray($errors);
                return $this->errorResponse('Validation error for order creation', $errorsList);
            }

            $description = $data['description'] ?? null;
            $order = $this->stockManagementService->createOrder($data['name'], $description);

            return $this->successResponse($order, 'Order created successfully', Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            return $this->errorResponse('Order creation failed', [$e->getMessage()]);
        }
    }

    #[OA\Put(
        path: '/orders/{id}',
        summary: 'Update an existing order',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: Order::class, groups: ['update']))
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Order updated successfully',
                content: new OA\JsonContent(ref: new Model(type: Order::class, groups: ['details']))
            )
        ]
    )]
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {  
            $order = $this->findOrder($id);

            $data = json_decode($request->getContent(), true);
     
            // Validate the data
            $errors = $this->validateUpdateOrderData($data);
            if (empty($data) || $errors->count() > 0) {                
                $errorsList = $this->errorsListToArray($errors);
                return $this->errorResponse('Validation error for order creation', $errorsList);
            }

            $description = $data['description'] ?? null;
            $name = $data['name'] ?? null;
            $order = $this->orderRepository->updateOrder($order, $name, $description);

            return $this->successResponse($order, 'Order updated successfully');

        } catch (\Throwable $e) {
            return $this->errorResponse('Order update exception', [$e->getMessage()]);
        }
    }

    #[OA\Get(
        path: '/orders/{id}',
        summary: 'Get order details',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Returns the order details',
                content: new OA\JsonContent(ref: new Model(type: Order::class, groups: ['details']))
            )
        ]
    )]
    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    public function orderDetails(int $id): JsonResponse
    {
        try {
            $order = $this->findOrder($id);
            $data = $this->serializer->serialize($order, 'json', ['groups' => ['details']]);

            return new JsonResponse($data, Response::HTTP_OK, [], true);
        } catch (\Throwable $e) {
            $code = Response::HTTP_BAD_REQUEST;
            if ($e instanceof EntityNotFoundException ){
                $code = Response::HTTP_NOT_FOUND;
            }
            return $this->errorResponse('Order details retrieval failed', [$e->getMessage()], $code);
        }
    }

    #[OA\Delete(
        path: '/orders/{id}',
        summary: 'Delete an order',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Order deleted successfully',
                content: new OA\JsonContent(ref: new Model(type: Order::class, groups: ['details']))
            )
        ]
    )]
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {            
            $order = $this->findOrder($id);

            $result = $this->stockManagementService->deleteOrder($order);
            if (!$result) {
                throw new \Exception('Order deletion failed');
            }

            return $this->successResponse(null, 'Order deleted successfully');
        } catch (\Throwable $e) {
            $code = Response::HTTP_BAD_REQUEST;
            if ($e instanceof EntityNotFoundException ){
                $code = Response::HTTP_NOT_FOUND;
            }
            return $this->errorResponse('Order deletion exception', [$e->getMessage()], $code);
        }
    }
    #[OA\Put(
        path: '/orders/{orderId}/products/{productId}',
        summary: 'Add a product to an order',
        parameters: [
            new OA\Parameter(
                name: 'orderId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'productId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'quantity', type: 'integer')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product added to order successfully',
                content: new OA\JsonContent(ref: new Model(type: Order::class, groups: ['details']))
            )
        ]
    )]
    #[Route('/{orderId}/products/{productId}', name: 'add_product', methods: ['PUT'])]
    public function addProduct(int $orderId, int $productId, Request $request, ValidatorInterface $validator): JsonResponse
    {
        try {
            $order = $this->findOrder($orderId);
            $data = json_decode($request->getContent(), true);

            $errors = $this->validateProductQuantity($data, $validator);            
            if ($errors->count() > 0) {            
                return $this->errorResponse('Validation error for product quantity', $this->errorsListToArray($errors));
            }

            $product = $this->findProduct($productId);
            $order = $this->stockManagementService->addProductToOrder($order, $product, $data['quantity']);

            return $this->successResponse($order, 'Product added to order successfully');
        } catch (\Throwable $e) {
            $code = Response::HTTP_BAD_REQUEST;
            if ($e instanceof EntityNotFoundException ){
                $code = Response::HTTP_NOT_FOUND;
            }
            return $this->errorResponse('Exception while adding product to order', [$e->getMessage()], $code);
        }
    }

    #[OA\Delete(
        path: '/orders/{orderId}/products/{productId}',
        summary: 'Remove a product from an order',
        parameters: [
            new OA\Parameter(
                name: 'orderId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'productId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'quantity', type: 'integer')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product removed from order successfully',
                content: new OA\JsonContent(ref: new Model(type: Order::class, groups: ['details']))
            )
        ]
    )]
    #[Route('/{orderId}/products/{productId}', name: 'remove_product', methods: ['DELETE'])]
    public function removeProduct(int $orderId, int $productId, Request $request, ValidatorInterface $validator): JsonResponse
    {
        try {
            $order = $this->findOrder($orderId);
            $data = json_decode($request->getContent(), true);

            $errors = $this->validateProductQuantity($data, $validator);            
            if ($errors->count() > 0) {            
                return $this->errorResponse('Validation error for product quantity', $this->errorsListToArray($errors));
            }

            $product = $this->findProduct($productId);         
            $this->stockManagementService->removeProductFromOrder($order, $product, $data['quantity']);

            return $this->successResponse($order, 'Product removed from order successfully');
        } catch (\Throwable $e) {
            $code = Response::HTTP_BAD_REQUEST;
            if($e instanceof EntityNotFoundException) {
                $code = Response::HTTP_NOT_FOUND;
            }
            return $this->errorResponse('Exception while removing product from order', [$e->getMessage()], $code);
        }
    }

    private function errorResponse(
        string $message, 
        ?array $errors = null,
        int $httpCode = Response::HTTP_BAD_REQUEST, 
        array $context = ['groups' => ['default', 'error']]): JsonResponse
    {
        $apiResponse = ApiResponse::errorResponse($message, $errors);
        $json = $this->serializer->serialize($apiResponse, 'json', $context);
        return new JsonResponse(json_decode($json), $httpCode);
    }

    private function successResponse(
        mixed $data, 
        string $message, 
        int $httpCode = Response::HTTP_OK, 
        array $context = ['groups' => ['default', 'success']]): JsonResponse
    {
        $apiResponse = ApiResponse::successResponse($data, $message);
        $json = $this->serializer->serialize($apiResponse, 'json', $context);        
        return new JsonResponse(json_decode($json), $httpCode); 
    }

    private function findOrder(int $id): Order
    {
        $order = $this->orderRepository->find($id);
        if (empty($order)) {
            throw new EntityNotFoundException('Order not found');
        }
        return $order;
    }

    private function findProduct(int $id): Product
    {
        $product = $this->productRepository->find($id);
        if (empty($product)) {
            throw new EntityNotFoundException('Product not found');
        }
        return $product;
    }

    private function validateOrderData(array $data): ConstraintViolationListInterface
    {
        $errors = $this->validator->validate($data, new Assert\Collection([
            'name' => [
                new Assert\NotBlank(),
                new Assert\Type('string'),
                new Assert\Length(['min' => 1])
            ],
            'description' => [
                new Assert\Type('string')
            ]
        ]));

        return $errors;
    }

    private function validateUpdateOrderData(array $data): ConstraintViolationListInterface
    {
        $errors = $this->validator->validate($data, new Assert\Collection([
            'name' => [
                new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(['min' => 1])
                ])
            ],
            'description' => [
                new Assert\Optional([
                    new Assert\Type('string')
                ])
            ]
        ]));

        return $errors;
    }

    private function validateProductQuantity(array $data): ConstraintViolationListInterface 
    {
        $errors = $this->validator->validate($data, new Assert\Collection([
            'quantity' => [
                new Assert\NotBlank(),
                new Assert\Type('integer'),
                new Assert\Positive()
            ]
        ]));

        return $errors;
    }

    private function errorsListToArray(ConstraintViolationListInterface $errors): array
    {
        $errorMessages = [];
        foreach ($errors as $fieldname => $error) {
            $errorMessages[] = sprintf('%s: %s', $error->getPropertyPath(), $error->getMessage());

        }
        return $errorMessages;
    }
}
