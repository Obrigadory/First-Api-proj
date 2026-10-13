<?php

namespace App\Database;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = 'mysql'; // Имя сервиса из docker-compose.yml
            $db   = 'shop_db';
            $user = 'root';
            $pass = 'secretpassword';

            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Ошибка подключения к БД: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        return self::$instance;
    }
}