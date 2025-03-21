<?php
$host = "127.0.0.1";
$user = "root";
$password = "root";
$database = "cadence";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>