<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'akina_db');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB() {
    try {
        $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

function initDB() {
    $pdo = new PDO("mysql:host=".DB_HOST.";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS ".DB_NAME);
    $pdo->exec("USE ".DB_NAME);
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS parametros (
        id INT AUTO_INCREMENT PRIMARY KEY,
        clave VARCHAR(50) UNIQUE NOT NULL,
        valor VARCHAR(255) NOT NULL
    )");
    
    $pdo->exec("INSERT IGNORE INTO parametros (clave, valor) VALUES 
        ('tiempo_vigencia_minutos', '60'),
        ('tiempo_view_pdf_minutos', '30')");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS clientes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_apellido VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        telefono VARCHAR(50) NOT NULL,
        marca VARCHAR(100),
        modelo VARCHAR(100),
        localidad VARCHAR(150),
        fecha DATE NOT NULL,
        pdf_path VARCHAR(500),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS enlaces (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        token VARCHAR(64) UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        viewed_at DATETIME NULL,
        FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
    )");
    
    return $pdo;
}
?>