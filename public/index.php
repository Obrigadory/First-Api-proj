<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\OrderController;

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

$controller = new OrderController();

if ($uri === '/orders') {
    switch ($method) {
        case 'GET':
            $id ? $controller->show($id) : $controller->index();
            break;

        case 'POST':
            $controller->store();
            break;

        case 'PUT':
            if ($id) {
                $controller->update($id);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Не указан ID заказа']);
            }
            break;

        case 'DELETE':
            if ($id) {
                $controller->delete($id);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Не указан ID заказа']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
}