<?php
$host = 'localhost';
$dbname = 'user_money';
$user = 'user_money'; // Ganti dengan username database Anda
$pass = 'Puputchen12$';     // Ganti dengan password database Anda
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Pada production, jangan tampilkan error detail ke user
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}