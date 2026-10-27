<?php

function getDatabase(): PDO
{
    $required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
    $env = [];

    foreach ($required as $key) {
        $value = getenv($key);
        if ($value === false || trim($value) === '') {
            throw new RuntimeException("Variable d'environnement manquante ou vide : {$key}");
        }
        $env[$key] = trim($value);
    }

    $port = getenv('DB_PORT');
    $port = ($port === false || trim($port) === '') ? '3306' : trim($port);

    if (!ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
        throw new RuntimeException('DB_PORT doit être un numéro de port valide.');
    }

    $caPath = dirname(__DIR__) . '/certs/ca.pem';

    if (!is_readable($caPath)) {
        throw new RuntimeException('Le certificat SSL certs/ca.pem est introuvable ou illisible.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $env['DB_HOST'],
        $port,
        $env['DB_NAME']
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        \Pdo\Mysql::ATTR_SSL_CA => $caPath,
        \Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT => true
    ];

    return new PDO($dsn, $env['DB_USER'], $env['DB_PASSWORD'], $options);
}
