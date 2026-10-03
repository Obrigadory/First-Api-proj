<?php

require_once __DIR__ . '/../models/Order.php';

class OrderController {
    private string $filePath;

    public function __construct() {
        // Поднимаемся на 2 уровня вверх: из controllers/ в корень, затем в data/
        $this->filePath = __DIR__ . '/../../data/orders.json';
    }

    // Вспомогательный метод: читать заказы из файла
    private function getOrders(): array {
        return file_exists($this->filePath)
            ? json_decode(file_get_contents($this->filePath), true) ?? []
            : [];
    }

    // Вспомогательный метод: сохранять заказы в файл
    private function saveOrders(array $orders): void {
        file_put_contents(
            $this->filePath,
            json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    // GET /orders
    public function index(): void {
        $orders = $this->getOrders();
        echo json_encode($orders, JSON_UNESCAPED_UNICODE);
    }

    // GET /orders?id=X
    public function show(int $id): void {
        $orders = $this->getOrders();
        foreach ($orders as $order) {
            if ($order['id'] === $id) {
                echo json_encode($order, JSON_UNESCAPED_UNICODE);
                return;
            }
        }
        http_response_code(404);
        echo json_encode(['error' => 'Заказ не найден'], JSON_UNESCAPED_UNICODE);
    }

    // POST /orders
    public function store(): void {
        $rawInput = file_get_contents('php://input');
        $newOrder = json_decode($rawInput, true);

        if (!$newOrder) {
            http_response_code(400);
            echo json_encode(['error' => 'Некорректный JSON'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $currentOrders = $this->getOrders();
        $id = count($currentOrders) + 1;

        try {
            $order = new Order(
                $id,
                $newOrder['destination'] ?? '',
                $newOrder['status'] ?? 'Новый',
                (float)($newOrder['weight'] ?? 0)
            );
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            return;
        }

        $currentOrders[] = $order->toArray();
        $this->saveOrders($currentOrders);

        http_response_code(201);
        echo json_encode(['message' => 'Заказ создан', 'data' => $order->toArray()], JSON_UNESCAPED_UNICODE);
    }

    // PUT /orders?id=X
    public function update(int $id): void {
        $rawInput = file_get_contents('php://input');
        $inputData = json_decode($rawInput, true);

        if (!$inputData) {
            http_response_code(400);
            echo json_encode(['error' => 'Некорректный JSON'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $orders = $this->getOrders();
        $found = false;

        foreach ($orders as &$order) {
            if ($order['id'] === $id) {
                $destination = $inputData['destination'] ?? $order['destination'];
                $status = $inputData['status'] ?? $order['status'];
                $weight = (float)($inputData['weight'] ?? $order['weight']);

                try {
                    $updateOrder = new Order($id, $destination, $status, $weight);
                    $order = $updateOrder->toArray();
                    $found = true;
                    break;
                } catch (InvalidArgumentException $e) {
                    http_response_code(400);
                    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                    return;
                }
            }
        }

        if (!$found) {
            http_response_code(404);
            echo json_encode(['error' => 'Заказ для обновления не найден'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->saveOrders($orders);
        http_response_code(200);
        echo json_encode(['message' => 'Заказ успешно обновлен'], JSON_UNESCAPED_UNICODE);
    }

    // DELETE /orders?id=X
    public function delete(int $id): void {
        $orders = $this->getOrders();
        $newOrders = [];
        $found = false;

        foreach ($orders as $order) {
            if ($order['id'] === $id) {
                $found = true;
                continue;
            }
            $newOrders[] = $order;
        }

        if (!$found) {
            http_response_code(404);
            echo json_encode(['error' => 'Заказ для удаления не найден'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->saveOrders($newOrders);
        http_response_code(200);
        echo json_encode(['message' => 'Заказ успешно удален'], JSON_UNESCAPED_UNICODE);
    }
}