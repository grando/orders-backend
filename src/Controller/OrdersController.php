<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use App\Model\ApiResponse;
use App\Repository\OrderProductRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\StockManagementService;
use Doctrine\ORM\EntityManagerInterface;
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

#[Route('orders', name: 'app_orders_')]
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

    #[Route('/test', name: 'test', methods: ['GET'])]
    public function test(): JsonResponse
    {
        try{
        $order = $this->orderRepository->find(1);
        $product = $this->productRepository->find(3);
        
        $list = $this->orderProductRepository->find(1);

        dd($list);
        } catch (\Throwable $e) {
            dd($e);
            return $this->errorResponse('Orders retrieval failed', [$e->getMessage()]);
        }
        /**

        $order = $this->orderRepository->find(1);
        $product = $this->productRepository->find(1);
        $this->productRepository->checkAndUpdateStockLevel($product, 1);
        $orderProduct = $this->orderProductRepository->getOrderProduct($order, $product);
        dd($orderProduct);
        $orderProduct = new OrderProduct();
        $orderProduct->setOrder($order)
            ->setProduct($product)
            ->setQuantity(1);
        $order->addOrderProduct($orderProduct);
        $this->entityManager->persist($order);
        $this->entityManager->flush();
        **/

        dd('test');

    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function index(NormalizerInterface $normalizer): JsonResponse
    {
        try{            
            $orders = $this->orderRepository->findAll();
            $data = $normalizer->normalize($orders, null, ['groups' => ['list']]);

            return $this->successResponse($data, 'Orders retrieved successfully');

        } catch (\Throwable $e) {
            return $this->errorResponse('Orders retrieval failed', [$e->getMessage()]);
        }
    }

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

    /*
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $order = $this->findOrder($id);

            $data = json_decode($request->getContent(), true);            
     
            $errors = $this->validateUpdateOrderData($data);
            if (empty($data) || $errors->count() > 0) {                
                $errorsList = $this->errorsListToArray($errors);
                return $this->errorResponse('Validation error for order creation', $errorsList);
            }

            $description = $data['description'] ?? null;
            $name = $data['name'] ?? null;
            $order = $this->stockManagementService->updateOrder($order, $name, $description);

            return $this->successResponse($order, 'Order updated successfully');

        } catch (\Throwable $e) {
            return $this->errorResponse('Order update exception', [$e->getMessage()]);
        }
    }
    */

    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    public function orderDetails(int $id): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/OrdersController.php',
        ]);
    }

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
            return $this->errorResponse('Order deletion exception', [$e->getMessage()]);
        }
    }

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
            return $this->errorResponse('Exception while adding product to order', [$e->getMessage()]);
        }
    }

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

            $apiResponse = ApiResponse::successResponse($order, 'Product removed from order successfully');
            $json = $this->serializer->serialize($apiResponse, 'json', ['groups' => ['default', 'success']]);
            return new JsonResponse($json, Response::HTTP_OK, [], true);
        } catch (\Throwable $e) {
            return $this->errorResponse('Exception while removing product from order', [$e->getMessage()]);
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
        return new JsonResponse($json, $httpCode, [], true);
    }

    private function successResponse(
        mixed $data, 
        string $message, 
        int $httpCode = Response::HTTP_OK, 
        array $context = ['groups' => ['default', 'success']]): JsonResponse
    {
        $apiResponse = ApiResponse::successResponse($data, $message);
        $json = $this->serializer->serialize($apiResponse, 'json', $context);
        return new JsonResponse($json, $httpCode, [], true); 
    }

    private function findOrder(int $id): Order
    {
        $order = $this->orderRepository->find($id);
        if (!$order) {
            throw new \Exception('Order not found');
        }
        return $order;
    }

    private function findProduct(int $id): Product
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            throw new \Exception('Product not found');
        }
        return $product;
    }

    private function validateOrderData(array $data): ConstraintViolationListInterface
    {
        $errors = $this->validator->validate($data, new Assert\Collection([
            'name' => [
                new Assert\NotBlank(),
                new Assert\Type('string')
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
                new Assert\Type('string')
            ],
            'description' => [
                new Assert\Type('string')
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
            $errorMessages[] = $error->getMessage();
        }
        return $errorMessages;
    }

}
