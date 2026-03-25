<?php

namespace System\Database;

use PDO;

final class PdoFactory
{
    public static function make(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            getenv('DB_HOST', 'set_your_host'),
            getenv('DB_PORT', 'set_your_port'),
            getenv('DB_DATABASE', 'set_your_database_name')
        );

        return new PDO($dsn, getenv('DB_USERNAME'), getenv('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
        ]);
    }
}