<?php

namespace App\Controllers;

use App\Models\Order;
use App\Database\Database;
use PDO;

class OrderController {

    // GET /orders
    public function index(): void {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM orders");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($orders, JSON_UNESCAPED_UNICODE);
    }

    // GET /orders?id=X
    public function show(int $id): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'Заказ не найден'], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode($order, JSON_UNESCAPED_UNICODE);
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

        try {
            // Используем модель только для валидации данных
            $orderObj = new Order(
                0, // ID сгенерирует база данных
                $newOrder['destination'] ?? '',
                $newOrder['status'] ?? 'Новый',
                (float)($newOrder['weight'] ?? 0)
            );
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO orders (destination, status, weight) VALUES (?, ?, ?)");
        $stmt->execute([
            $newOrder['destination'],
            $newOrder['status'] ?? 'Новый',
            (float)($newOrder['weight'] ?? 0)
        ]);

        $id = (int)$db->lastInsertId();

        // Достаем созданную запись для ответа
        $stmtShow = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmtShow->execute([$id]);
        $createdOrder = $stmtShow->fetch(PDO::FETCH_ASSOC);

        http_response_code(201);
        echo json_encode(['message' => 'Заказ успешно создан в MySQL', 'data' => $createdOrder], JSON_UNESCAPED_UNICODE);
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

        $db = Database::getConnection();
        
        // Проверяем существование заказа
        $stmtCheck = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmtCheck->execute([$id]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Заказ для обновления не найден'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $destination = $inputData['destination'] ?? $existing['destination'];
        $status = $inputData['status'] ?? $existing['status'];
        $weight = (float)($inputData['weight'] ?? $existing['weight']);

        try {
            new Order($id, $destination, $status, $weight); // Валидация
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            return;
        }

        $stmtUpdate = $db->prepare("UPDATE orders SET destination = ?, status = ?, weight = ? WHERE id = ?");
        $stmtUpdate->execute([$destination, $status, $weight, $id]);

        http_response_code(200);
        echo json_encode(['message' => 'Заказ успешно обновлен'], JSON_UNESCAPED_UNICODE);
    }

    // DELETE /orders?id=X
    public function delete(int $id): void {
        $db = Database::getConnection();
        
        $stmtCheck = $db->prepare("SELECT id FROM orders WHERE id = ?");
        $stmtCheck->execute([$id]);
        
        if (!$stmtCheck->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Заказ для удаления не найден'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $stmtDelete = $db->prepare("DELETE FROM orders WHERE id = ?");
        $stmtDelete->execute([$id]);

        http_response_code(200);
        echo json_encode(['message' => 'Заказ успешно удален'], JSON_UNESCAPED_UNICODE);
    }
}