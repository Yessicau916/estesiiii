<?php

declare(strict_types=1);

class Database {
    private static ?PDO $connection = null;

    public static function getConnection(): PDO {
        if (self::$connection === null) {
            $host = 'localhost';
            $db   = 'dbappsig_';
            $user = 'appsig';
            $pass = 'appsig123';
            $charset = 'utf8mb4';
            $port = 3305;

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            self::$connection = new PDO($dsn, $user, $pass, $options);
        }

        return self::$connection;
    }
}